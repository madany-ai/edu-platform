"use client";

export default function OfflinePage() {
  return (
    <div style={{
      display: 'flex',
      flexDirection: 'column',
      alignItems: 'center',
      justifyContent: 'center',
      minHeight: '100vh',
      background: '#121212',
      color: '#fff',
      fontFamily: 'system-ui, sans-serif',
      textAlign: 'center',
      padding: '2rem',
    }}>
      <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#8d6638" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <line x1="1" y1="1" x2="23" y2="23" />
        <path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55" />
        <path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39" />
        <path d="M10.71 5.05A16 16 0 0 1 22.56 9" />
        <path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88" />
        <path d="M8.53 16.11a6 6 0 0 1 6.95 0" />
        <line x1="12" y1="20" x2="12.01" y2="20" />
      </svg>
      <h1 style={{ fontSize: '1.5rem', marginTop: '1.5rem', fontWeight: 600 }}>لا يوجد اتصال بالإنترنت</h1>
      <p style={{ color: '#888', marginTop: '0.5rem' }}>يرجى التحقق من اتصالك بالإنترنت والمحاولة مرة أخرى</p>
      <button
        onClick={() => window.location.reload()}
        style={{
          marginTop: '1.5rem',
          padding: '0.75rem 2rem',
          background: 'transparent',
          border: '1px solid #333',
          color: '#fff',
          borderRadius: '0.5rem',
          cursor: 'pointer',
          fontSize: '1rem',
        }}
      >
        إعادة المحاولة
      </button>
    </div>
  );
}
