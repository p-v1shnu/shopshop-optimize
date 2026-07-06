import crypto from 'crypto'

const publicKey = `-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAsMRJKL4M/p6hVBkfZ9P8
fDXBersbGlHOs4vJrtq+vL2F0UYf5SLajAQFMdA2gEmzjk0pxlUfIK88f8vzso02
POy/ZeixCSMtsaz4CnLWiuEzkQkTSInq/eze0+CrwlMqv1UcIjDGsuih6h6Z2NYz
DTdRJp/wiFa9vGZ3aFyKMDxFhi7MJm4Ee2PYDuPuLtSnCVaExkZ9ZJHxcEtK1OmZ
6ryL4zmXfgHzTQDVYKPmYlDD/n6tYIs/UhkeWR+rWBdsnNuT+GHzrrvaDkHw6LiB
LPs3Jl9DOPlyzjt99zCWnU17+2r+lg0tE5lSiHbpepz7wSh9Gz7qLhQXdZeaOZYS
MwIDAQAB
-----END PUBLIC KEY-----`

const requestData = {
  "id": "SUTLO3TFHSEL3X7W",
  "order_code": "9626",
  "order_amount": 10,
  "payment_status": "paid",
  "shipping_phone": "2077363677",
  "shipping_amount": 0,
  "shipping_name": "TENG",
  "shipping_province": "OU",
  "shipping_district": "ໄຊ",
  "shipping_village": "ວຽງຈະເລີນ",
  "created_at": "2024-09-18T23:28:17+07:00",
  "orderDetails": [
    {
      "shop_order_id": "SUTLO3TFHSEL3X7W",
      "shop_product_id": 1,
      "quantity": 9,
      "price": 1,
      "type": "normal",
    },
    {
      "shop_order_id": "SUTLO3TFHSEL3X7W",
      "shop_product_id": 13,
      "quantity": 1,
      "price": 1,
      "type": "premium",
    }
  ],
  "signature": "SO+oJkDc4gs4uO+KlqB+oGosxrvdP8MDZ7hU5OGw8jW7jPksSHrW3HLospYecYdBSIg0qfsY6q1Nnzp9ccNb5CzeuO+Tw4R4/yAMnhxzs0j290zULsjhoO4RgKJBHGg4HA1JoRYOEupk1HlKnssBdE+OyrCnlmLvsnSWsSZJ/OMbTjDnbtY+In45ZhYAN8yH9Bq3v3Uvv/zHnCg7VZGkh8kXaO3pb9mcABMisP+578qD/DiCHVEu8NPmbsSQXRLurlqp0H2Us+hkWvMYp4D6SRrjMKeZRRHvvf3xoiGyVn0w/Fi4tYb9KA8hJLLx8NFUtdsVi72M5kVtnUab3xVzag=="
}

const { signature: signatureBase64, ...data} = requestData

// Decode the Base64 signature
const signature = Buffer.from(signatureBase64, 'base64')

// Create a verifier
const verify = crypto.createVerify('SHA256')
verify.update(JSON.stringify(data))
verify.end()

// Verify the signature
const isVerified = verify.verify(publicKey, signature)
console.log('Signature verified:', isVerified);
