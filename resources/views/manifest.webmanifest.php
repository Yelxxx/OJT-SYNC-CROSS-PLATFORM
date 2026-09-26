{
  "name": "{{ env('VITE_APP_NAME', 'OJT Sync') }}",
  "short_name": "{{ env('VITE_APP_NAME', 'OJT Sync') }}",
  "description": "OJT Sync - On-the-Job Training Management System",
  "start_url": "/dashboard",
  "display": "standalone",
  "background_color": "#33954B",
  "theme_color": "#33954B",
  "orientation": "portrait-primary",
  "icons": [
    {
      "src": "{{ asset('images/logo-192.png') }}",
      "sizes": "192x192",
      "type": "image/png",
      "purpose": "any maskable"
    },
    {
      "src": "{{ asset('images/logo-512.png') }}",
      "sizes": "512x512",
      "type": "image/png",
      "purpose": "any maskable"
    }
  ]
}