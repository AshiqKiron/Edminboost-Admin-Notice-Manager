import { test, expect } from '@playwright/test';
import { pluginUrl } from '../helpers/anm-api';

test.describe('Settings accessibility', () => {
	test('tablist has aria roles and keyboard-focusable tabs', async ({ page }) => {
		await page.goto(pluginUrl('-settings'));

		const tablist = page.locator('[role="tablist"]');
		await expect(tablist).toBeVisible();

		const tabs = tablist.locator('[role="tab"]');
		await expect(tabs.first()).toHaveAttribute('aria-selected');

		const selected = page.locator('[role="tab"][aria-selected="true"]');
		const panelId = await selected.getAttribute('aria-controls');
		expect(panelId).toBeTruthy();

		const panel = page.locator(`#${panelId}`);
		await expect(panel).toBeVisible();
		await expect(panel).toHaveAttribute('role', 'tabpanel');
	});
});
