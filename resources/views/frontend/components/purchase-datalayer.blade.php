@script
  <script>
    // Pushed by ShopCheckoutPage::showPaymentSuccess() once an order is paid (GA4/Facebook "purchase").
    function getCookie(name) {
      const match = document.cookie.match(new RegExp('(^|; )' + name + '=([^;]*)'))
      return match ? decodeURIComponent(match[2]) : null
    }

    function getFbp() {
      return getCookie('_fbp')
    }

    function getFbc() {
      let fbc = getCookie('_fbc')
      if (!fbc) {
        const fbclid = new URLSearchParams(window.location.search).get('fbclid')
        if (fbclid) {
          fbc = 'fb.1.' + Date.now() + '.' + fbclid
        }
      }
      return fbc
    }

    $wire.on('order-paid', function (event) {
      const data = event || {}
      const order = data.order || {}
      const user = data.user || {}
      const payload = {
        event: 'purchase',
        order_id: order.id,
        user_id: user.id,
        created_at: order.created_at,
        gender: user.gender,
        first_name: user.first_name,
        last_name: user.last_name,
        ph: user.phone,
        user_province: user.province,
        campaign_code: order.campaign_code || null,
        ecommerce: {
          transaction_id: order.id,
          value: order.value,
          tax: null, // TODO: send later
          shipping: null, // TODO: send later
          shipping_channel: order.shipping_channel,
          currency: order.currency,
          coupon: null, // TODO: send later
          items: order.items || []
        },
        event_id: Date.now().toString(),
        fbc: getFbc(),
        fbp: getFbp()
      }

      window.dataLayer = window.dataLayer || []

      // GTM's container script loads ASYNC, so window.google_tag_manager may still be undefined here
      // even when a container is configured — do NOT gate on it. Always push with eventCallback; GTM
      // replays the dataLayer queue once it loads, then invokes the callback after firing the tags
      // (our "sent" signal). There is no redirect here, so the event is never lost — the timeout only
      // controls a fallback "no container" warning for observability.
      let logged = false
      payload.eventTimeout = 1000
      payload.eventCallback = function () {
        if (logged) return
        logged = true
        console.log('[GTM] "purchase" (order ' + payload.order_id + ') sent to Google successfully (event_id ' + payload.event_id + ').', payload)
      }
      // Clear any prior ecommerce state so leftover keys can't merge into this purchase (GA4 recommendation).
      window.dataLayer.push({ ecommerce: null })
      window.dataLayer.push(payload)

      setTimeout(function () {
        if (!logged && !window.google_tag_manager) {
          console.warn('[GTM] No container detected — "purchase" (order ' + payload.order_id + ') was pushed to dataLayer but may not have reached Google.', payload)
        }
      }, 1500)
    })
  </script>
@endscript
