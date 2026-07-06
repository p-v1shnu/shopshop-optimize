import Echo from '@ably/laravel-echo'
import * as Ably from 'ably'

window.Ably = Ably
window.Echo = new Echo({
  broadcaster: 'ably',
})

window.Echo.connector.ably.connection.on(stateChange => {
  if (stateChange.current === 'connected') {
    console.log('Connected to Echo Server')
  }
})
