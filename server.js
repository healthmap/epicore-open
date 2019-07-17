const phpServer = require('php-server');
 
(async () => {
    const server = await phpServer({
      port: 8000
    });
    console.log(`PHP server running at ${server.url}`)
})();