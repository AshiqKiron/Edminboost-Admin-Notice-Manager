import { defineConfig, devices } from '@playwright/test';
import dotenv from 'dotenv';
import path from 'path';

dotenv.config({ path: path.join(__dirname, '.env') });

const baseURL = process.env.E2E_BASE_URL || 'http://localhost:8888/wordpress';

export default defineConfig({
	testDir: './specs',
	fullyParallel: false,
	forbidOnly: !!process.env.CI,
	retries: process.env.CI ? 2 : 0,
	workers: 1,
	reporter: [
		['list'],
		['html', { open: 'never', outputFolder: path.join(__dirname, '../../test-results/playwright-report') }],
		['json', { outputFile: path.join(__dirname, '../../test-results/playwright-results.json') }],
	],
	use: {
		baseURL,
		trace: 'on-first-retry',
		screenshot: 'only-on-failure',
		video: 'retain-on-failure',
		storageState: path.join(__dirname, '.auth/admin.json'),
	},
	globalSetup: path.join(__dirname, 'global-setup.ts'),
	projects: [
		{
			name: 'chromium',
			use: { ...devices['Desktop Chrome'] },
		},
	],
	outputDir: path.join(__dirname, '../../test-results/playwright-artifacts'),
	timeout: 60_000,
});
