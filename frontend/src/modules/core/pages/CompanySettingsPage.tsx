import { Link, useLocation } from '@tanstack/react-router';
import { cn } from '@/lib/utils';
import { Building2, Users, Shield, Bell, Database, Palette } from 'lucide-react';

const settingsNav = [
  { label: 'Company', path: '/core/settings/company', icon: Building2 },
  { label: 'Users', path: '/core/users', icon: Users },
  { label: 'Roles', path: '/core/roles', icon: Shield },
  { label: 'Notifications', path: '/core/settings/notifications', icon: Bell },
  { label: 'Integrations', path: '/core/settings/integrations', icon: Database },
  { label: 'Appearance', path: '/core/settings/appearance', icon: Palette },
];

export function CompanySettingsPage() {
  const location = useLocation();

  return (
    <div className="flex h-full gap-6">
      {/* Settings Sidebar */}
      <aside className="w-64 shrink-0">
        <div className="rounded-lg border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 overflow-hidden">
          <div className="px-4 py-3 border-b border-neutral-200 dark:border-neutral-800">
            <h2 className="font-semibold text-neutral-900 dark:text-neutral-100">Settings</h2>
          </div>
          <nav className="p-2">
            {settingsNav.map((item) => {
              const isActive = location.pathname === item.path;
              return (
                <Link
                  key={item.path}
                  to={item.path}
                  className={cn(
                    'flex items-center gap-3 px-3 py-2 text-sm rounded-md transition-colors',
                    isActive
                      ? 'bg-primary/10 text-primary font-medium'
                      : 'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-neutral-100'
                  )}
                >
                  <item.icon className="h-4 w-4" />
                  {item.label}
                </Link>
              );
            })}
          </nav>
        </div>
      </aside>

      {/* Settings Content */}
      <div className="flex-1">
        <div className="rounded-lg border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6">
          <h1 className="text-2xl font-bold mb-2">Company Settings</h1>
          <p className="text-neutral-600 dark:text-neutral-400 mb-6">
            Manage your company information and preferences.
          </p>

          <div className="space-y-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label className="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
                  Company Name
                </label>
                <input
                  type="text"
                  className="w-full rounded-md border border-neutral-200 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-neutral-700 dark:bg-neutral-800"
                  placeholder="Enter company name"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
                  Email
                </label>
                <input
                  type="email"
                  className="w-full rounded-md border border-neutral-200 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-neutral-700 dark:bg-neutral-800"
                  placeholder="company@example.com"
                />
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
                Address
              </label>
              <textarea
                rows={3}
                className="w-full rounded-md border border-neutral-200 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-neutral-700 dark:bg-neutral-800"
                placeholder="Enter company address"
              />
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label className="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
                  Phone
                </label>
                <input
                  type="tel"
                  className="w-full rounded-md border border-neutral-200 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-neutral-700 dark:bg-neutral-800"
                  placeholder="+212 522 123 456"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
                  Tax ID
                </label>
                <input
                  type="text"
                  className="w-full rounded-md border border-neutral-200 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-neutral-700 dark:bg-neutral-800"
                  placeholder="Enter tax ID"
                />
              </div>
            </div>

            <div className="pt-4 border-t border-neutral-200 dark:border-neutral-800">
              <button className="px-4 py-2 bg-primary text-primary-foreground rounded-md text-sm font-medium hover:bg-primary/90 transition-colors">
                Save Changes
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

export default CompanySettingsPage;
