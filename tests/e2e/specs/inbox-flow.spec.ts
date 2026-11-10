import { test, expect } from '@playwright/test';
import { getAnmNonce, pluginUrl, postAnmAjax } from '../helpers/anm-api';

test.describe('Inbox capture flow', () => {
	test('capture notice via interceptor AJAX then see row in inbox', async ({ page, request, baseURL }) => {
		await page.goto(pluginUrl());
		const nonce = await getAnmNonce(page);
		const cookies = (await page.context().cookies())
			.map((c) => `${c.name}=${c.value}`)
			.join('; ');

		await postAnmAjax(
			request,
			baseURL!,
			'edminboost_anm_toggle_enabled',
			{ nonce, enabled: '1' },
			cookies
		);

		const unique = `E2E capture ${Date.now()}`;
		const html = `<div class="notice notice-warning"><p>${unique}</p></div>`;

		const capture = await postAnmAjax(
			request,
			baseURL!,
			'edminboost_anm_capture_notice',
			{
				nonce,
				html,
				source_plugin: 'E2E Playwright',
			},
			cookies
		);

		expect(capture.success).toBe(true);

		await page.goto(pluginUrl('-inbox'));
		await expect(page.locator('.edminboost-anm-wrap')).toContainText(unique, { timeout: 15_000 });
	});
});
