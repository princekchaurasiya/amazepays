import axios from 'axios';

/**
 * Axios for legacy/global usage (Inertia primarily uses fetch).
 * ESM only — do not use require(); this project uses "type": "module".
 */
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
