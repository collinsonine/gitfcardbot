module.exports = {
    apps: [
        {
            name: 'giftcardbot-bridge',
            script: 'bot.js',
            cwd: __dirname,
            instances: 1,
            autorestart: true,
            watch: false,
            max_memory_restart: '512M',
            env: {
                NODE_ENV: 'production',
                BRIDGE_PORT: 3001,
            },
            error_file: '../storage/logs/bridge-err.log',
            out_file: '../storage/logs/bridge-out.log',
            log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
            merge_logs: true,
            max_restarts: 10,
            restart_delay: 5000,
        },
    ],
};
