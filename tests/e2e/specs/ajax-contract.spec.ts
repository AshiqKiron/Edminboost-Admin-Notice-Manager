import { test, expect } from '@playwright/test';
import { getAnmNonce, pluginUrl, postAnmAjax } from '../helpers/anm-api';

test.describe('AJAX contract', () => {
	test('rejects inbox list without valid nonce', async ({ page, request, baseURL }) => {
		await page.goto(pluginUrl());
		const cookies = (await page.context().cookies())
			.map((c) => `${c.name}=${c.value}`)
			.join('; ');

		const bad = await postAnmAjax(request, baseURL!, 'edminboost_anm_inbox_list', { nonce: 'bad' }, cookies);
		expect(bad.success).toBeFalsy();
	});

	test('inbox list returns HTML when authenticated', async ({ page, request, baseURL }) => {
		await page.goto(pluginUrl('-inbox'));
		const nonce = await getAnmNonce(page);
		const cookies = (await page.context().cookies())
			.map((c) => `${c.name}=${c.value}`)
			.join('; ');

		const result = await postAnmAjax(
			request,
			baseURL!,
			'edminboost_anm_inbox_list',
			{
				nonce,
				filter: 'all',
				orderby: 'intercepted_at',
				order: 'desc',
			},
			cookies
		);

		expect(result.success).toBe(true);
		expect(result.data).toBeDefined();
	});
});
