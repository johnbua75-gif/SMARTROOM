// Simple reservation API client for devices or scripts
// Usage: node scripts/reservation-client.js --email user@example.com --password secret --room 1 --start 2026-05-01T10:00:00 --end 2026-05-01T11:00:00 --notes "Device report"
// Requires dependencies: axios tough-cookie axios-cookiejar-support
// Install: npm install axios tough-cookie axios-cookiejar-support

const axios = require('axios');
const { CookieJar } = require('tough-cookie');
const { wrapper } = require('axios-cookiejar-support');

function argv(name, fallback = undefined) {
  const idx = process.argv.indexOf('--' + name);
  if (idx === -1) return fallback;
  return process.argv[idx + 1];
}

async function main() {
  const email = argv('email');
  const password = argv('password');
  const roomId = argv('room');
  const startAt = argv('start');
  const endAt = argv('end');
  const notes = argv('notes') || '';

  if (!email || !password || !roomId || !startAt || !endAt) {
    console.error('Missing required args. See header for usage.');
    process.exit(2);
  }

  const jar = new CookieJar();
  const client = wrapper(axios.create({ jar, withCredentials: true }));

  try {
    // Login to obtain session cookie (expects JSON response when Accept: application/json)
    const loginRes = await client.post('http://10.246.60.31:8000/login', {
      email,
      password
    }, {
      headers: { 'Accept': 'application/json' }
    });

    if (loginRes.status !== 200 && loginRes.status !== 302) {
      console.error('Login failed', loginRes.status, loginRes.data || '');
      process.exit(1);
    }

    // POST reservation to API (session cookie will be sent automatically)
    const res = await client.post('http://10.246.60.31:8000/api/v1/reservations', {
      classroom_id: Number(roomId),
      start_at: startAt,
      end_at: endAt,
      notes: notes
    }, {
      headers: { 'Accept': 'application/json' }
    });

    console.log('Reservation response:', res.status, res.data);
  } catch (err) {
    if (err.response && err.response.data) {
      console.error('Error:', err.response.status, err.response.data);
    } else {
      console.error('Error:', err.message || err);
    }
    process.exit(1);
  }
}

main();
