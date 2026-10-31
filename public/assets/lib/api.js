export async function request(action = 'catalog', { signal, token, parameters = {} } = {}) {
  const url = new URL('/awc/api.php', location.origin);
  url.search = new URLSearchParams({ action, ...parameters });
  const response = await fetch(url, {
    signal: signal ?? AbortSignal.timeout(30000),
    method: action === 'retrieve' ? 'POST' : 'GET',
    headers: token ? { 'X-CSRF-Token': token } : {},
    credentials: 'same-origin',
  });
  const data = await response.json();
  if (!response.ok) throw new Error(data.error ?? 'Unable to complete this request. Please try again.');
  return data;
}
