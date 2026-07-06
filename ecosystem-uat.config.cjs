module.exports = {
  apps : [
    // {
    //   name: 'shopshop-uat-jdb-worker',
    //   script: './workers/jdb.cjs',
    //   instances: 1,
    //   autorestart: true,
    //   cron_restart: '0 0 * * *',
    //   watch: false,
    //   max_memory_restart: '1G',
    //   log_date_format: null,
    //   merge_logs: true,
    //   combine_logs: true,
    //   error_file: './workers/logs/jdb-worker.log',
    //   out_file: '/dev/null',
    //   env: {
    //     'NODE_ENV': 'production',
    //   }
    // },
    {
      name: 'shopshop-uat-bcel-worker',
      script: './workers/bcel.cjs',
      instances: 1,
      autorestart: true,
      cron_restart: '0 0 * * *',
      watch: false,
      max_memory_restart: '1G',
      log_date_format: null,
      merge_logs: true,
      combine_logs: true,
      error_file: './workers/logs/bcel-worker.log',
      out_file: '/dev/null',
      env: {
        'NODE_ENV': 'production',
      }
    },
  ]
}
