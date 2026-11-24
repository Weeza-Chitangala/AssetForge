import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api/v1',
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
});

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('assetforge_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('assetforge_token');
      window.dispatchEvent(new Event('assetforge:unauthorized'));
    }
    return Promise.reject(error);
  }
);

export async function downloadBlob(
  url: string,
  params?: Record<string, string | undefined>,
  fallbackFilename = 'export'
): Promise<void> {
  const cleanParams = Object.fromEntries(
    Object.entries(params || {}).filter(([, v]) => v !== undefined && v !== '')
  );

  const response = await api.get(url, {
    params: cleanParams,
    responseType: 'blob',
  });

  const disposition = response.headers['content-disposition'] as string | undefined;
  let filename = fallbackFilename;
  if (disposition) {
    const match = disposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
    if (match?.[1]) {
      filename = match[1].replace(/['"]/g, '').trim();
    }
  }

  const blobUrl = window.URL.createObjectURL(new Blob([response.data]));
  const link = document.createElement('a');
  link.href = blobUrl;
  link.setAttribute('download', filename);
  document.body.appendChild(link);
  link.click();
  link.remove();
  window.URL.revokeObjectURL(blobUrl);
}

export default api;
