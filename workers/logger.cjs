const pino = require('pino')
const dayjs = require('dayjs')

const streams = [
  {
    stream: require('file-stream-rotator').getStream({
      filename: `${__dirname}/logs/worker-%DATE%`,
      frequency: 'daily',
      date_format: 'YYYYMMDD',
      size: '1G',
      max_logs: '365',
      audit_file: `${__dirname}/logs/.audit.json`,
      extension: '.log',
      create_symlink: true,
      symlink_name: 'today.log',
    }),
  },
  { stream: require('pino-pretty')({ colorize: true }) },
]

const logger = pino(
  {
    level: process.env.LOG_LEVEL || 'debug',
    timestamp: () => `,"time":"${dayjs().format('YYYY-MM-DD HH:mm:ss')}"`,
    formatters: {
      level: (label) => {
        return { level: label }
      },
    },
    mixin() {
      return {
        app: process.env.LOGGER_APP_NAME,
      }
    },
    redact: [
      'req.headers.authorization',
      'req.headers["x-token"]',
    ],
  },
  pino.multistream(streams),
)

exports.logger = logger
