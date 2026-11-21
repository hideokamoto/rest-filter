import { defineConfig } from 'astro/config';
import starlight from '@astrojs/starlight';

export default defineConfig({
  site: 'https://hideokamoto.github.io',
  base: '/wp-api-rest-filter',
  integrations: [
    starlight({
      title: 'WP API REST Filter',
      social: [
        {
          icon: 'github',
          label: 'GitHub',
          href: 'https://github.com/hideokamoto/wp-api-rest-filter',
        },
      ],
      sidebar: [
        { label: 'Getting Started', slug: 'getting-started' },
        { label: 'Whitelist', slug: 'whitelist' },
        {
          label: '設定方法',
          items: [
            { label: 'wp-admin', slug: 'guides/wp-admin' },
            { label: 'wp-cli', slug: 'guides/wp-cli' },
            { label: 'wp-api', slug: 'guides/wp-api' },
          ],
        },
        { label: '使用例', slug: 'examples' },
        { label: 'セキュリティ', slug: 'security' },
      ],
    }),
  ],
});
