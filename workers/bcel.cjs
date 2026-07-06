process.env.LOGGER_APP_NAME = 'shopshop-bcel-worker'
require('dotenv').config()

const { logger } = require('./logger.cjs')
const PubNub = require('pubnub')
const https = require('https')
const axios = require('axios')
const crypto = require('crypto')
const dayjs = require('dayjs')
const customParseFormat = require('dayjs/plugin/customParseFormat')
dayjs.extend(customParseFormat)

if (process.env.NODE_ENV !== 'production') {
  axios.defaults.httpsAgent = new https.Agent({
    rejectUnauthorized: false,
  })
}

async function main() {
  const appKey = process.env.APP_KEY

  const pubnub = new PubNub({
    subscribeKey : process.env.BCEL_QR_PUBNUB_SUBKEY,
    ssl          : true,
    uuid         : process.env.BCEL_QR_PUBNUB_USERID,
  })

  const pubnubChannels = [
    `mcid-${process.env.BCEL_QR_MC_ID}-${process.env.BCEL_QR_MC_CODE}`,
  ]
  const sinceLastMinute = 60 // GET ALL MESSAGES SINCE LAST 60 MINUTE
  let timetoken = new Date().getTime() - sinceLastMinute * 60 * 1000 + '0000'

  pubnub.addListener({
    status: (statusEvent) => {
      if (statusEvent.category === 'PNConnectedCategory') {
        logger.info({
          msg: 'PubNub Connected',
          mode: process.env.NODE_ENV,
          channel: pubnubChannels,
          timetoken: timetoken,
        })
      }
      else if (statusEvent.category === 'PNNetworkUpCategory') {
        logger.info({ msg: 'PubNub Reconnected' })
        pubnub.unsubscribe({ channels: pubnubChannels })
        pubnub.subscribe({ channels: pubnubChannels })
      }
    },
    message: async (message) => {
      const payment = JSON.parse(message.message)
      const billNumber = payment.uuid ? payment.uuid.toString() : ''

      if (billNumber.startsWith(process.env.PAYMENT_UUID_PREFIX)) {
        try {

          // Generate Signature
          const payload = JSON.stringify(payment)
          const signature = crypto.createHmac('sha256', appKey)
            .update(payload)
            .digest('hex')

          // Need to send payload as string because JS and PHP handle JSON differently
          const requestInfo = {
            url: process.env.BCEL_QR_WEBHOOK_URL,
            method: 'POST',
            data: {
              payload,
              signature,
            },
          }

          logger.info({
            billNumber,
            msg: 'Webhook request',
            data: requestInfo,
          })

          const { data } = await axios.post(requestInfo.url, requestInfo.data, {
            validateStatus: () => true,
          })

          logger.info({
            billNumber,
            msg: 'Webhook response',
            data,
          })

        } catch (error) {

          logger.error({
            billNumber,
            msg: 'Webhook Error',
            data: {
              message: error.message,
              stack: error.stack,
            }
          })

        }
      }

    }
  })

  pubnub.subscribe({ channels: pubnubChannels, timetoken: timetoken })
  logger.info({ msg: 'PubNub BCEL worker is running' })
}

main()
