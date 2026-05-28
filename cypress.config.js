const { defineConfig } = require('cypress')

module.exports = defineConfig({
  e2e: {
    baseUrl: 'https://localhost',
    chromeWebSecurity: false,
    viewportWidth: 1280,
    viewportHeight: 900,
    defaultCommandTimeout: 8000,
    scrollBehavior: 'center',
    setupNodeEvents(on) {
      // Allow self-signed cert on local FrankenPHP dev server
      on('before:browser:launch', (browser, launchOptions) => {
        if (browser.family === 'chromium') {
          launchOptions.args.push('--ignore-certificate-errors')
        }
        return launchOptions
      })
    },
  },
})