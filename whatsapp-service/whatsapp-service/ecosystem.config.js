module.exports = {
  apps: [
    {
      name: "balancexe-whatsapp-service",
      script: "src/server.js",
      cwd: __dirname,
      instances: 1,
      exec_mode: "fork",
      autorestart: true,
      shutdown_with_message: true,
      kill_timeout: 15000,
      max_memory_restart: "512M",
      env: {
        NODE_ENV: "production",
        PORT: 3100
      }
    }
  ]
};
