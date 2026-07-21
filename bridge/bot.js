require('dotenv').config();
const { Client, LocalAuth, List, MessageMedia } = require('whatsapp-web.js');
const express = require('express');
const axios = require('axios');
const qrcodeTerminal = require('qrcode-terminal');
const QRCode = require('qrcode');
const os = require('os');

if (!process.env.LARAVEL_LISTENER_URL) {
  console.error('FATAL: LARAVEL_LISTENER_URL environment variable is not set.');
  process.exit(1);
}
const LARAVEL_LISTENER_URL = process.env.LARAVEL_LISTENER_URL;
const BRIDGE_PORT = process.env.BRIDGE_PORT || 3001;
const BRIDGE_SECRET = process.env.BRIDGE_SECRET || 'change-me';

const startTime = Date.now();
let client;
let latestQr = null;
let isRestarting = false;
let messageCount = 0;
let lastMessageAt = null;
let retryCount = 0;
const MAX_RETRIES = 5;
const RETRY_DELAY_MS = 5000;

function randomDelay() {
    return Math.floor(Math.random() * 2500) + 2500;
}

async function sendTyping(phone) {
    try {
        const chatId = phone.includes('@') ? phone : `${phone}@c.us`;
        const chat = await client.getChatById(chatId);
        await chat.sendStateTyping();
    } catch (e) {
        // silently fail
    }
}

function getClientStatus() {
    if (!client) return 'not_initialized';
    if (isRestarting) return 'restarting';
    try {
        if (client.info && client.info.wid && client.info.wid.user) return 'connected';
    } catch (e) {
        // client not ready
    }
    if (latestQr) return 'awaiting_qr';
    return 'initializing';
}

function delay(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

async function createClient() {
    if (client) {
        client.destroy().catch(() => {});
    }

    latestQr = null;

    client = new Client({
        authStrategy: new LocalAuth(),
        puppeteer: {
            headless: true,
            args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage', '--disable-gpu'],
        },
    });

    client.on('qr', (qr) => {
        latestQr = qr;
        qrcodeTerminal.generate(qr, { small: true });
        console.log('Scan the QR code above with WhatsApp Web.');
    });

    client.on('ready', () => {
        latestQr = null;
        retryCount = 0;
        console.log('WhatsApp client is ready.');
    });

    client.on('authenticated', () => {
        console.log('WhatsApp client authenticated.');
    });

    client.on('auth_failure', (msg) => {
        console.error('Auth failure:', msg);
    });

    client.on('disconnected', async (reason) => {
        console.log('WhatsApp client disconnected:', reason);
        latestQr = null;

        if (retryCount < MAX_RETRIES) {
            retryCount++;
            console.log(`Reconnecting in ${RETRY_DELAY_MS / 1000}s (attempt ${retryCount}/${MAX_RETRIES})...`);
            await delay(RETRY_DELAY_MS);
            await createClient();
        } else {
            console.error('Max reconnection attempts reached. Exiting.');
            process.exit(1);
        }
    });

    client.on('message_create', async (msg) => {
        if (msg.from.includes('@g.us') || msg.from === 'status@broadcast') return;

        messageCount++;
        lastMessageAt = new Date().toISOString();

        const phone = msg.from;
        const hasMedia = msg.hasMedia;
        let mediaPath = null;

        if (hasMedia) {
            try {
                const media = await msg.downloadMedia();
                mediaPath = `/tmp/wa_media_${Date.now()}.${media.mimetype.split('/')[1] || 'bin'}`;
                require('fs').writeFileSync(mediaPath, media.data, 'base64');
            } catch (e) {
                console.error('Media download failed:', e.message);
            }
        }

        const payload = {
            phone,
            message: msg.body,
            name: msg._data?.notifyName || msg._data?.pushname || phone,
            has_media: hasMedia,
            media_path: mediaPath,
        };

        const headers = {
            'Accept': 'application/json',
        };

        try {
            await axios.post(LARAVEL_LISTENER_URL, payload, {
                headers,
                timeout: 15000,
            });
        } catch (error) {
            console.error('Failed to forward to listener:', error.message);
        }
    });

    try {
        await client.initialize();
    } catch (err) {
        console.error('Client initialize() failed:', err.message);
        if (retryCount < MAX_RETRIES) {
            retryCount++;
            console.log(`Retrying in ${RETRY_DELAY_MS / 1000}s (attempt ${retryCount}/${MAX_RETRIES})...`);
            await delay(RETRY_DELAY_MS);
            await createClient();
        } else {
            console.error('Max reconnection attempts reached. Exiting.');
            process.exit(1);
        }
    }
}

async function gracefulShutdown(signal) {
    console.log(`\n${signal} received. Shutting down gracefully...`);
    try {
        if (client) {
            await client.destroy();
        }
    } catch (e) {
        // ignore
    }
    process.exit(0);
}

process.on('SIGTERM', () => gracefulShutdown('SIGTERM'));
process.on('SIGINT', () => gracefulShutdown('SIGINT'));

const app = express();
app.use(express.json());

function verifyBridgeSecret(req, res, next) {
    const secret = req.headers['x-bridge-secret'];
    if (secret !== BRIDGE_SECRET) {
        return res.status(403).json({ error: 'Forbidden' });
    }
    next();
}

app.get('/api/health', (req, res) => {
    const status = getClientStatus();
    const uptimeMs = Date.now() - startTime;

    res.json({
        status: 'ok',
        bridge_status: status,
        client_ready: status === 'connected',
        phone: null,
        has_qr: latestQr !== null,
        uptime_seconds: Math.floor(uptimeMs / 1000),
        uptime_human: formatUptime(uptimeMs),
        messages_processed: messageCount,
        last_message_at: lastMessageAt,
        memory_mb: Math.round(process.memoryUsage().rss / 1024 / 1024),
        node_version: process.version,
        pid: process.pid,
        started_at: new Date(startTime).toISOString(),
    });
});

app.get('/api/health/phone', (req, res) => {
    try {
        const phone = client?.info?.wid?.user || null;
        res.json({ phone });
    } catch (e) {
        res.json({ phone: null });
    }
});

app.post('/api/restart', verifyBridgeSecret, async (req, res) => {
    if (isRestarting) {
        return res.status(409).json({ error: 'Restart already in progress' });
    }

    isRestarting = true;
    console.log('WhatsApp client restart requested via API.');

    res.json({ success: true, message: 'Restarting WhatsApp client...' });

    setTimeout(async () => {
        try {
            if (client) {
                await client.destroy();
            }
        } catch (e) {
            // ignore
        }
        latestQr = null;
        isRestarting = false;
        await createClient();
        console.log('WhatsApp client restarted.');
    }, 1000);
});

app.post('/api/send-message', async (req, res) => {
    const { phone, message, interactive } = req.body;

    if (!phone) {
        return res.status(400).json({ error: 'phone is required' });
    }

    if (getClientStatus() !== 'connected') {
        return res.status(503).json({ error: 'WhatsApp client not connected' });
    }

    const chatId = phone.includes('@') ? phone : `${phone}@c.us`;

    try {
        await sendTyping(chatId);

        setTimeout(async () => {
            try {
                if (interactive && interactive.type === 'list') {
                    const list = new List(
                        interactive.body || message || '',
                        interactive.buttonText || 'Select',
                        interactive.sections || [],
                        interactive.title || undefined,
                        interactive.footer || undefined,
                    );
                    await client.sendMessage(chatId, list);
                    console.log(`Sent list to ${phone}: ${(interactive.body || message || '').substring(0, 50)}...`);
                } else {
                    await client.sendMessage(chatId, message);
                    console.log(`Sent to ${phone}: ${message.substring(0, 50)}...`);
                }
            } catch (err) {
                console.error(`Send failed to ${phone}:`, err.message);
            }
        }, randomDelay());

        res.json({ success: true });
    } catch (error) {
        console.error('Error initiating send:', error.message);
        res.status(500).json({ error: 'Failed to send message' });
    }
});

app.post('/api/send-media', async (req, res) => {
    const { phone, media, mimetype, filename, caption } = req.body;

    if (!phone || !media || !mimetype) {
        return res.status(400).json({ error: 'phone, media, and mimetype are required' });
    }

    if (getClientStatus() !== 'connected') {
        return res.status(503).json({ error: 'WhatsApp client not connected' });
    }

    const chatId = phone.includes('@') ? phone : `${phone}@c.us`;

    try {
        const messageMedia = new MessageMedia(mimetype, media, filename);
        await client.sendMessage(chatId, messageMedia, { caption: caption || undefined });
        console.log(`Sent media to ${phone}: ${filename || mimetype}`);
        res.json({ success: true });
    } catch (error) {
        console.error(`Media send failed to ${phone}:`, error.message);
        res.status(500).json({ error: 'Failed to send media' });
    }
});

app.get('/api/qr', async (req, res) => {
    if (!latestQr) {
        return res.json({ qr: null });
    }

    try {
        const qrDataUrl = await QRCode.toDataURL(latestQr);
        res.json({ qr: qrDataUrl });
    } catch (error) {
        console.error('QR generation error:', error.message);
        res.status(500).json({ error: 'Failed to generate QR code' });
    }
});

app.post('/api/disconnect', async (req, res) => {
    try {
        if (client) {
            await client.logout();
            await client.destroy();
        }
    } catch (error) {
        console.error('Disconnect error:', error.message);
    }

    latestQr = null;
    createClient();

    res.json({ success: true });
});

function formatUptime(ms) {
    const seconds = Math.floor(ms / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    if (days > 0) return `${days}d ${hours % 24}h ${minutes % 60}m`;
    if (hours > 0) return `${hours}h ${minutes % 60}m`;
    if (minutes > 0) return `${minutes}m ${seconds % 60}s`;
    return `${seconds}s`;
}

createClient();

app.listen(BRIDGE_PORT, '127.0.0.1', () => {
    console.log(`WhatsApp bridge listening on http://127.0.0.1:${BRIDGE_PORT}`);
});
