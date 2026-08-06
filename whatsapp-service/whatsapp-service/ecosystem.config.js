module.exports = {
  apps: [
    {
      name: "balancexe-whatsapp-service",
      script: "src/server.js",
      cwd: ".",
      instances: 1,
      exec_mode: "fork",
      autorestart: true,
      max_memory_restart: "512M",
      env: {
        NODE_ENV: "production",
        PORT: 3100
      }
    }
  ]
};
