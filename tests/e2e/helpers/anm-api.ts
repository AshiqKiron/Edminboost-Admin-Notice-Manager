import type { APIRequestContext, Page } from '@playwright/test';

const PAGE_SLUG = 'edminboost-admin-notice-manager';

export type AnmAjaxSuccess = { success: true; data?: Record<string, unknown> };
export type AnmAjaxError = { success: false; data?: { message?: string } };

/**
 * Read nonce from localized edminboostAnmData on any plugin admin screen.
 */
export async function getAnmNonce(page: Page): Promise<string> {
	const nonce = await page.evaluate(() => {
		const data = (window as unknown as { edminboostAnmData?: { nonce?: string } }).edminboostAnmData;
		return data?.nonce ?? '';
	});
	if (!nonce) {
		throw new Error('edminboostAnmData.nonce not found — open a plugin admin page first.');
	}
	return nonce;
}

export async function postAnmAjax(
	request: APIRequestContext,
	baseURL: string,
	action: string,
	fields: Record<string, string>,
	cookies: string
): Promise<AnmAjaxSuccess | AnmAjaxError> {
	const body = new URLSearchParams({ action, ...fields });
	const response = await request.post(`${baseURL}/wp-admin/admin-ajax.php`, {
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded',
			Cookie: cookies,
		},
		data: body.toString(),
	});
	const json = (await response.json()) as AnmAjaxSuccess | AnmAjaxError;
	return json;
}

export function pluginUrl(segment: '' | '-inbox' | '-rules' | '-settings' | '-billing' = ''): string {
	return `/wp-admin/admin.php?page=${PAGE_SLUG}${segment}`;
}

export { PAGE_SLUG };
