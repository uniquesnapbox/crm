module.exports = {
  apps: [
    {
      name: "usb-crm-queue",
      cwd: __dirname,
      script: "artisan",
      interpreter: "php",
      args: "queue:work database --sleep=3 --tries=1 --timeout=120",
      autorestart: true,
      restart_delay: 2000,
      max_memory_restart: "256M",
      windowsHide: true,
    },
  ],
};
