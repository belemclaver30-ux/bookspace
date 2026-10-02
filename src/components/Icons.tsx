// Jeu d'icônes SVG repris du prototype BookSpace.

export const Icon = {
  Search: () => (
    <svg width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" viewBox="0 0 24 24">
      <circle cx="11" cy="11" r="8" /><path d="m21 21-4.35-4.35" />
    </svg>
  ),
  Home: ({ active }: { active?: boolean }) => (
    <svg width="22" height="22" fill={active ? "#FC4E00" : "none"} stroke={active ? "#FC4E00" : "#94A3B8"} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" viewBox="0 0 24 24">
      <path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5z" /><path d="M9 21V12h6v9" />
    </svg>
  ),
  Calendar: ({ active }: { active?: boolean }) => (
    <svg width="22" height="22" fill="none" stroke={active ? "#FC4E00" : "#94A3B8"} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" viewBox="0 0 24 24">
      <rect x="3" y="4" width="18" height="18" rx="2" /><path d="M16 2v4M8 2v4M3 10h18" />
    </svg>
  ),
  User: ({ active }: { active?: boolean }) => (
    <svg width="22" height="22" fill="none" stroke={active ? "#FC4E00" : "#94A3B8"} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" viewBox="0 0 24 24">
      <circle cx="12" cy="8" r="4" /><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" />
    </svg>
  ),
  Grid: ({ active }: { active?: boolean }) => (
    <svg width="22" height="22" fill="none" stroke={active ? "#FC4E00" : "#94A3B8"} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" viewBox="0 0 24 24">
      <rect x="3" y="3" width="7" height="7" rx="1" /><rect x="14" y="3" width="7" height="7" rx="1" /><rect x="3" y="14" width="7" height="7" rx="1" /><rect x="14" y="14" width="7" height="7" rx="1" />
    </svg>
  ),
  Star: ({ filled }: { filled?: boolean }) => (
    <svg width="13" height="13" fill={filled ? "#F3BB00" : "none"} stroke="#F3BB00" strokeWidth="1.5" viewBox="0 0 24 24">
      <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26" />
    </svg>
  ),
  Location: () => (
    <svg width="13" height="13" fill="none" stroke="#64748B" strokeWidth="1.8" strokeLinecap="round" viewBox="0 0 24 24">
      <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" /><circle cx="12" cy="9" r="2.5" />
    </svg>
  ),
  ArrowLeft: () => (
    <svg width="22" height="22" fill="none" stroke="#BD3100" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" viewBox="0 0 24 24">
      <path d="M19 12H5m0 0 7-7m-7 7 7 7" />
    </svg>
  ),
  Check: () => (
    <svg width="28" height="28" fill="none" stroke="white" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" viewBox="0 0 24 24">
      <path d="M20 6 9 17l-5-5" />
    </svg>
  ),
  ChevronRight: () => (
    <svg width="16" height="16" fill="none" stroke="#94A3B8" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" viewBox="0 0 24 24">
      <path d="m9 18 6-6-6-6" />
    </svg>
  ),
  Bell: () => (
    <svg width="20" height="20" fill="none" stroke="#BD3100" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" viewBox="0 0 24 24">
      <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" /><path d="M13.73 21a2 2 0 0 1-3.46 0" />
    </svg>
  ),
  Music: () => (
    <svg width="16" height="16" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" viewBox="0 0 24 24">
      <path d="M9 18V5l12-2v13" /><circle cx="6" cy="18" r="3" /><circle cx="18" cy="16" r="3" />
    </svg>
  ),
  Football: () => (
    <svg width="16" height="16" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" viewBox="0 0 24 24">
      <circle cx="12" cy="12" r="10" /><path d="M12 2v4m0 12v4M2 12h4m12 0h4m-3.17-6.83-2.83 2.83M7.17 7.17 4.34 4.34M20.66 19.66l-2.83-2.83M7.17 16.83l-2.83 2.83" />
    </svg>
  ),
  Plus: () => (
    <svg width="20" height="20" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" viewBox="0 0 24 24">
      <path d="M12 5v14M5 12h14" />
    </svg>
  ),
  Wifi: () => (
    <svg width="14" height="14" fill="none" stroke="#64748B" strokeWidth="1.8" strokeLinecap="round" viewBox="0 0 24 24">
      <path d="M5 12.55a11 11 0 0 1 14.08 0" /><path d="M1.42 9a16 16 0 0 1 21.16 0" /><path d="M8.53 16.11a6 6 0 0 1 6.95 0" /><circle cx="12" cy="20" r="1" fill="#64748B" />
    </svg>
  ),
  Logout: () => (
    <svg width="18" height="18" fill="none" stroke="#EF4444" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" viewBox="0 0 24 24">
      <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" /><polyline points="16 17 21 12 16 7" /><line x1="21" y1="12" x2="9" y2="12" />
    </svg>
  ),
  Settings: () => (
    <svg width="18" height="18" fill="none" stroke="#64748B" strokeWidth="1.8" strokeLinecap="round" viewBox="0 0 24 24">
      <circle cx="12" cy="12" r="3" /><path d="M12 1v2m0 18v2M4.22 4.22l1.42 1.42m12.72 12.72 1.42 1.42M1 12h2m18 0h2M4.22 19.78l1.42-1.42m12.72-12.72 1.42-1.42" />
    </svg>
  ),
  TrendUp: () => (
    <svg width="18" height="18" fill="none" stroke="#10B981" strokeWidth="2" strokeLinecap="round" viewBox="0 0 24 24">
      <polyline points="22 7 13.5 15.5 8.5 10.5 2 17" /><polyline points="16 7 22 7 22 13" />
    </svg>
  ),
};

/** Icônes de navigation (héritent de la couleur du texte via currentColor). */
const nav = (paths: React.ReactNode) => () => (
  <svg width="18" height="18" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round" strokeLinejoin="round" viewBox="0 0 24 24">
    {paths}
  </svg>
);

export const NavIcon = {
  Dashboard: nav(<><rect x="3" y="3" width="7" height="9" rx="1" /><rect x="14" y="3" width="7" height="5" rx="1" /><rect x="14" y="12" width="7" height="9" rx="1" /><rect x="3" y="16" width="7" height="5" rx="1" /></>),
  Calendar: nav(<><rect x="3" y="4" width="18" height="18" rx="2" /><path d="M16 2v4M8 2v4M3 10h18" /></>),
  Building: nav(<><path d="M3 21h18M6 21V7l6-4 6 4v14M10 9h.01M14 9h.01M10 13h.01M14 13h.01M10 17h.01M14 17h.01" /></>),
  Check: nav(<><path d="m9 12 2 2 4-4" /><circle cx="12" cy="12" r="9" /></>),
  Alert: nav(<><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" /><path d="M12 9v4M12 17h.01" /></>),
  Sliders: nav(<><line x1="4" y1="21" x2="4" y2="14" /><line x1="4" y1="10" x2="4" y2="3" /><line x1="12" y1="21" x2="12" y2="12" /><line x1="12" y1="8" x2="12" y2="3" /><line x1="20" y1="21" x2="20" y2="16" /><line x1="20" y1="12" x2="20" y2="3" /><line x1="1" y1="14" x2="7" y2="14" /><line x1="9" y1="8" x2="15" y2="8" /><line x1="17" y1="16" x2="23" y2="16" /></>),
  User: nav(<><circle cx="12" cy="8" r="4" /><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" /></>),
  Bell: nav(<><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" /><path d="M13.73 21a2 2 0 0 1-3.46 0" /></>),
};
