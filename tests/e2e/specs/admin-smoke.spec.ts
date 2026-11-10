import { test, expect } from '@playwright/test';
import { pluginUrl } from '../helpers/anm-api';

const pages: Array<{ path: string; heading: RegExp }> = [
	{ path: pluginUrl(), heading: /dashboard|notice manager/i },
	{ path: pluginUrl('-inbox'), heading: /inbox/i },
	{ path: pluginUrl('-rules'), heading: /rules|auto-rules|filter/i },
	{ path: pluginUrl('-settings'), heading: /settings/i },
	{ path: pluginUrl('-billing'), heading: /billing|plan/i },
];

test.describe('Admin smoke — plugin screens load', () => {
	for (const { path, heading } of pages) {
		test(`loads ${path}`, async ({ page }) => {
			await page.goto(path);
			await expect(page.locator('.edminboost-anm-wrap, .wrap')).toBeVisible();
			await expect(page.getByRole('heading', { level: 1 })).toContainText(heading);
			await expect(page.locator('script[src*="edminboost-anm-admin"]')).toHaveCount(1);
		});
	}

	test('settings tabs switch without console errors', async ({ page }) => {
		const errors: string[] = [];
		page.on('pageerror', (err) => errors.push(err.message));

		await page.goto(pluginUrl('-settings'));
		const tabs = page.locator('[role="tab"]');
		const count = await tabs.count();
		expect(count).toBeGreaterThan(3);

		for (let i = 0; i < Math.min(count, 6); i++) {
			await tabs.nth(i).click();
			await page.waitForTimeout(150);
		}

		expect(errors).toEqual([]);
	});
});
