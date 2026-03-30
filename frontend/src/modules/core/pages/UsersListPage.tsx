import { useState } from 'react'
import { Plus, Search, Edit2, Trash2, Shield, Mail, User as UserIcon } from 'lucide-react'
import { usePermissions } from '@/core/auth/useAuth'

// Mock Data
const MOCK_USERS = [
  { id: '1', name: 'Admin User', email: 'admin@epsilon.com', role: 'Super Admin', status: 'Active', territory: 'All' },
  { id: '2', name: 'John Doe', email: 'john@epsilon.com', role: 'Sales Manager', status: 'Active', territory: 'North Region' },
  { id: '3', name: 'Jane Smith', email: 'jane@epsilon.com', role: 'Field Rep', status: 'Inactive', territory: 'South Zone' },
]

export function UsersListPage() {
  const { can } = usePermissions()
  const [search, setSearch] = useState('')

  const filteredUsers = MOCK_USERS.filter(u => 
    u.name.toLowerCase().includes(search.toLowerCase()) || 
    u.email.toLowerCase().includes(search.toLowerCase())
  )

  return (
    <div className="p-6 max-w-7xl mx-auto space-y-6">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-neutral-900 dark:text-neutral-100">Users</h1>
          <p className="text-sm text-neutral-500 dark:text-neutral-400">Manage system access and permissions</p>
        </div>
        
        {can('users.create') && (
          <button className="flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors">
            <Plus className="h-4 w-4" />
            Add User
          </button>
        )}
      </div>

      <div className="bg-white dark:bg-neutral-900 shadow-sm border border-neutral-200 dark:border-neutral-800 rounded-lg overflow-hidden">
        <div className="p-4 border-b border-neutral-200 dark:border-neutral-800 flex items-center justify-between">
          <div className="relative w-full max-w-sm">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-neutral-400" />
            <input 
              type="text" 
              placeholder="Search users..." 
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full pl-9 pr-4 py-2 bg-neutral-50 dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50"
            />
          </div>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm whitespace-nowrap">
            <thead className="bg-neutral-50 dark:bg-neutral-800/50 text-neutral-500 dark:text-neutral-400">
              <tr>
                <th className="px-6 py-3 font-medium">User</th>
                <th className="px-6 py-3 font-medium">Role</th>
                <th className="px-6 py-3 font-medium">Territory</th>
                <th className="px-6 py-3 font-medium">Status</th>
                <th className="px-6 py-3 font-medium text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
              {filteredUsers.map((user) => (
                <tr key={user.id} className="hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition-colors">
                  <td className="px-6 py-4">
                    <div className="flex items-center gap-3">
                      <div className="h-10 w-10 rounded-full bg-neutral-200 dark:bg-neutral-700 flex items-center justify-center text-neutral-500 dark:text-neutral-400">
                        <UserIcon className="h-5 w-5" />
                      </div>
                      <div>
                        <div className="font-medium text-neutral-900 dark:text-neutral-100">{user.name}</div>
                        <div className="flex items-center gap-1 text-xs text-neutral-500 dark:text-neutral-400">
                          <Mail className="h-3 w-3" />
                          {user.email}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td className="px-6 py-4">
                    <div className="flex items-center gap-1.5">
                      <Shield className="h-4 w-4 text-primary" />
                      <span className="text-neutral-700 dark:text-neutral-300">{user.role}</span>
                    </div>
                  </td>
                  <td className="px-6 py-4 text-neutral-600 dark:text-neutral-400">
                    {user.territory}
                  </td>
                  <td className="px-6 py-4">
                    <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                      user.status === 'Active' 
                        ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' 
                        : 'bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-400'
                    }`}>
                      {user.status}
                    </span>
                  </td>
                  <td className="px-6 py-4 text-right">
                    <div className="flex items-center justify-end gap-2">
                      {can('users.update') && (
                        <button className="p-1.5 text-neutral-500 hover:text-primary transition-colors rounded-md hover:bg-neutral-100 dark:hover:bg-neutral-800">
                          <Edit2 className="h-4 w-4" />
                        </button>
                      )}
                      {can('users.delete') && (
                        <button className="p-1.5 text-neutral-500 hover:text-red-500 transition-colors rounded-md hover:bg-neutral-100 dark:hover:bg-neutral-800">
                          <Trash2 className="h-4 w-4" />
                        </button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
              
              {filteredUsers.length === 0 && (
                <tr>
                  <td colSpan={5} className="px-6 py-8 text-center text-neutral-500 dark:text-neutral-400">
                    No users found matching your search.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}

export default UsersListPage;
