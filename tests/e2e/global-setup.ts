import { chromium, type FullConfig } from '@playwright/test';
import fs from 'fs';
import path from 'path';

const authDir = path.join(__dirname, '.auth');
const authFile = path.join(authDir, 'admin.json');

async function globalSetup(config: FullConfig): Promise<void> {
	const baseURL = config.projects[0]?.use?.baseURL || 'http://localhost:8888/wordpress';
	const user = process.env.E2E_WP_USER || 'admin';
	const password = process.env.E2E_WP_PASSWORD || 'password';

	fs.mkdirSync(authDir, { recursive: true });

	const browser = await chromium.launch();
	const page = await browser.newPage();

	await page.goto(`${baseURL}/wp-login.php`);
	await page.fill('#user_login', user);
	await page.fill('#user_pass', password);
	await page.click('#wp-submit');
	await page.waitForURL(/wp-admin/);

	await page.context().storageState({ path: authFile });
	await browser.close();
}

export default globalSetup;
