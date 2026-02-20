// @ts-check
import { defineConfig } from 'astro/config';
import starlight from '@astrojs/starlight';

// https://astro.build/config
export default defineConfig({
	site: 'https://php-temporal.github.io',
	base: '/php-temporal',
	integrations: [
		starlight({
			title: 'PHP Temporal',
			description: 'A PHP port of the JavaScript Temporal API — precise, immutable date/time types for PHP 8.4+',
			social: [
				{ icon: 'github', label: 'GitHub', href: 'https://github.com/php-temporal/php-temporal' },
			],
			sidebar: [
				{
					label: 'Getting Started',
					items: [
						{ label: 'Introduction', slug: 'index' },
						{ label: 'Installation', slug: 'getting-started/installation' },
						{ label: 'Quick Start', slug: 'getting-started/quick-start' },
					],
				},
				{
					label: 'Core Concepts',
					items: [
						{ label: 'Immutability', slug: 'concepts/immutability' },
						{ label: 'ISO 8601 Parsing', slug: 'concepts/parsing' },
						{ label: 'Overflow Handling', slug: 'concepts/overflow' },
						{ label: 'Exceptions', slug: 'concepts/exceptions' },
					],
				},
				{
					label: 'API Reference',
					items: [
						{ label: 'PlainDate', slug: 'api/plain-date' },
						{ label: 'PlainTime', slug: 'api/plain-time' },
						{ label: 'PlainDateTime', slug: 'api/plain-date-time' },
						{ label: 'Duration', slug: 'api/duration' },
						{ label: 'Instant', slug: 'api/instant' },
						{ label: 'ZonedDateTime', slug: 'api/zoned-date-time' },
						{ label: 'TimeZone', slug: 'api/time-zone' },
						{ label: 'Calendar', slug: 'api/calendar' },
						{ label: 'PlainYearMonth', slug: 'api/plain-year-month' },
						{ label: 'PlainMonthDay', slug: 'api/plain-month-day' },
						{ label: 'Temporal\\Now', slug: 'api/now' },
					],
				},
			],
			customCss: [],
		}),
	],
});
