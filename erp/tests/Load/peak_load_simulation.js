import http from 'k6/http';
import { check, sleep } from 'k6';
import { uuidv4 } from 'https://jslib.k6.io/k6-utils/1.4.0/index.js';

export const options = {
  stages: [
    { duration: '30s', target: 20 }, // Ramp up to 20 users
    { duration: '1m', target: 20 },  // Stay at 20 users
    { duration: '30s', target: 0 },  // Ramp down
  ],
  thresholds: {
    http_req_duration: ['p(95)<500'], // 95% of requests must complete below 500ms
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost';
const API_TOKEN = __ENV.API_TOKEN || 'your-token-here';

export default function () {
  const headers = {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${API_TOKEN}`,
  };

  // Simulate Stock Receiving (High Volume)
  const movementId = uuidv4();
  const payload = JSON.stringify({
    article_unite_id: 1,
    depot_id: 1,
    company_id: 1,
    quantite: Math.floor(Math.random() * 100) + 1,
    notes: 'Load Test Simulation',
  });

  const res = http.post(`${BASE_URL}/api/erp/movements`, payload, { headers });

  check(res, {
    'status is 201 or 202': (r) => [201, 202].includes(r.status),
    'transaction time OK': (r) => r.timings.duration < 200,
  });

  // Pull metrics (observability check)
  const metricsRes = http.get(`${BASE_URL}/api/metrics`, { headers });
  check(metricsRes, { 'metrics OK': (r) => r.status === 200 });

  sleep(Math.random() * 2 + 1); // Think time
}
