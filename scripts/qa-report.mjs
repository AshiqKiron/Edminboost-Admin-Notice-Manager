#!/usr/bin/env node
/**
 * Summarize PHPUnit and Playwright JSON output for quick QA review.
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.join(__dirname, '..');

const pwJson = path.join(root, 'test-results/playwright-results.json');
const phpCache = path.join(root, '.phpunit.result.cache');

let exitCode = 0;

console.log('\n=== EdminBoost ANM QA summary ===\n');

if (fs.existsSync(pwJson)) {
	const data = JSON.parse(fs.readFileSync(pwJson, 'utf8'));
	const failed = [];
	for (const suite of data.suites ?? []) {
		for (const spec of suite.specs ?? []) {
			for (const test of spec.tests ?? []) {
				for (const result of test.results ?? []) {
					if (result.status !== 'passed') {
						failed.push(`${spec.title} › ${test.title}: ${result.status}`);
					}
				}
			}
		}
	}
	console.log(`Playwright: ${data.stats?.expected ?? 0} expected, ${failed.length} non-passing`);
	if (failed.length) {
		exitCode = 1;
		failed.forEach((line) => console.log(`  - ${line}`));
	}
} else {
	console.log('Playwright: no results (run npm run test:e2e first)');
}

if (fs.existsSync(phpCache)) {
	console.log('PHPUnit: cache present — re-run npm run test:php for fresh results');
} else {
	console.log('PHPUnit: run npm run test:php and inspect terminal output');
}

console.log('\nArtifacts: test-results/playwright-report/, playwright traces on retry\n');
process.exit(exitCode);
