#!/usr/bin/env node
const { spawn } = require('child_process');

console.log('Starting PHP development server...');

const php = spawn('php', ['-S', '0.0.0.0:5000'], {
    cwd: 'public',
    stdio: 'inherit'
});

php.on('close', (code) => {
    console.log(`PHP server exited with code ${code}`);
});

process.on('SIGINT', () => {
    console.log('Shutting down PHP server...');
    php.kill();
    process.exit();
});