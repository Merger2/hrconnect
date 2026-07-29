#!/usr/bin/env node

import { spawn } from 'node:child_process';
import process from 'node:process';

const useProcessGroups = process.platform !== 'win32';
const reverbHost = process.env.REVERB_DEV_HOST || '127.0.0.1';

const services = [
    {
        name: 'reverb',
        command: 'php',
        args: ['artisan', 'reverb:start', `--host=${reverbHost}`, '--port=8081'],
    },
    {
        name: 'queue',
        command: 'php',
        args: ['artisan', 'queue:work', 'database', '--queue=maintenance,default', '--tries=3', '--timeout=1800'],
    },
    {
        name: 'vite',
        command: 'bun',
        args: ['run', 'dev'],
    },
    {
        name: 'laravel',
        command: 'php',
        args: ['artisan', 'serve'],
    },
];

const children = [];
let stopping = false;

function startService(service) {
    console.log(`Starting ${service.name}: ${service.command} ${service.args.join(' ')}`);

    const child = spawn(service.command, service.args, {
        stdio: 'inherit',
        detached: useProcessGroups,
        env: process.env,
    });

    children.push({ ...service, child });

    child.once('error', (error) => {
        if (stopping) {
            return;
        }

        console.error(`\n${service.name} failed to start: ${error.message}`);
        shutdown(1);
    });

    child.once('exit', (code, signal) => {
        if (stopping) {
            return;
        }

        const exitCode = code ?? 1;
        const reason = signal ? `signal ${signal}` : `exit code ${exitCode}`;

        console.log(`\n${service.name} stopped with ${reason}.`);
        shutdown(exitCode);
    });
}

function stopChild(child) {
    if (child.exitCode !== null || child.signalCode !== null) {
        return;
    }

    if (typeof child.pid !== 'number') {
        return;
    }

    try {
        if (useProcessGroups) {
            process.kill(-child.pid, 'SIGTERM');
        } else {
            child.kill('SIGTERM');
        }
    } catch (error) {
        if (error.code !== 'ESRCH') {
            console.error(`Failed to stop process ${child.pid}: ${error.message}`);
        }
    }
}

function shutdown(code = 0) {
    if (stopping) {
        return;
    }

    stopping = true;

    if (children.length > 0) {
        console.log('\nStopping local dev services...');
    }

    for (const { child } of children) {
        stopChild(child);
    }

    setTimeout(() => process.exit(code), 300);
}

process.once('SIGINT', () => shutdown(130));
process.once('SIGTERM', () => shutdown(143));
process.once('exit', () => {
    for (const { child } of children) {
        stopChild(child);
    }
});

for (const service of services) {
    startService(service);
}

console.log('');
console.log('Local dev services are running.');
console.log('Laravel: http://127.0.0.1:8000');
console.log(`Reverb:  ${reverbHost}:8081`);
console.log('Press Ctrl+C to stop everything.');
