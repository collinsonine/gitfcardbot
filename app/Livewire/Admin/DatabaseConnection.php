<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DatabaseConnection extends Component
{
    public string $connection = 'mysql';

    public string $host = '127.0.0.1';

    public string $port = '3306';

    public string $database = 'giftcardbot';

    public string $username = 'root';

    public string $password = '';

    public ?string $testResult = null;

    public bool $testSuccess = false;

    public bool $saved = false;

    public function mount(): void
    {
        $driver = config('database.default');
        $this->connection = in_array($driver, ['mysql', 'mariadb', 'pgsql', 'sqlsrv']) ? $driver : 'mysql';
        $this->host = config("database.connections.{$this->connection}.host", '127.0.0.1');
        $this->port = (string) config("database.connections.{$this->connection}.port", '3306');
        $this->database = config("database.connections.{$this->connection}.database", 'giftcardbot');
        $this->username = config("database.connections.{$this->connection}.username", 'root');
        $this->password = config("database.connections.{$this->connection}.password", '');
    }

    public function testConnection(): void
    {
        $this->reset('testResult', 'testSuccess');

        $originalConfig = config("database.connections.{$this->connection}");

        try {
            config([
                "database.connections.{$this->connection}.host" => $this->host,
                "database.connections.{$this->connection}.port" => (int) $this->port,
                "database.connections.{$this->connection}.database" => $this->database,
                "database.connections.{$this->connection}.username" => $this->username,
                "database.connections.{$this->connection}.password" => $this->password,
            ]);

            DB::purge($this->connection);
            DB::reconnect($this->connection);
            DB::connection($this->connection)->getPdo();

            $this->testSuccess = true;
            $this->testResult = 'Connection successful!';
        } catch (\Exception $e) {
            $this->testSuccess = false;
            $this->testResult = 'Connection failed: '.$e->getMessage();
        }
    }

    public function save(): void
    {
        $this->validate([
            'host' => 'required|string',
            'port' => 'required|numeric',
            'database' => 'required|string',
            'username' => 'required|string',
        ]);

        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            $this->testResult = '.env file not found.';
            $this->testSuccess = false;

            return;
        }

        $envContent = file_get_contents($envPath);

        $replacements = [
            'DB_CONNECTION' => $this->connection,
            'DB_HOST' => $this->host,
            'DB_PORT' => $this->port,
            'DB_DATABASE' => $this->database,
            'DB_USERNAME' => $this->username,
            'DB_PASSWORD' => $this->password,
        ];

        foreach ($replacements as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            $escapedValue = str_replace(['\\', '$', '"'], ['\\\\', '\$', '\"'], $value);

            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, "{$key}={$escapedValue}", $envContent);
            } else {
                $envContent .= "\n{$key}={$escapedValue}";
            }
        }

        file_put_contents($envPath, $envContent);

        $this->saved = true;
        session()->flash('saved', 'Database configuration saved. Changes will apply on next request.');
    }

    public function render()
    {
        return view('livewire.admin.database-connection')
            ->layout('layouts.admin', ['title' => 'DB Connection']);
    }
}
