import { api } from '@/core/api/client';
import { PaginatedResponse } from '@/core/api/types';

export interface User {
  id: string;
  name: string;
  email: string;
  role_id: string | null;
  territory_id: string | null;
  active: boolean;
  created_at: string;
}

export interface Territory {
  id: string;
  name: string;
  parent_id: string | null;
  level: number;
  children?: Territory[];
}

export interface Role {
  id: string;
  name: string;
  permissions: string[];
}

// CQRS Commands defining explicit intents
export interface CreateUserCommand {
  name: string;
  email: string;
  role_id: string | null;
  territory_id: string | null;
}

export interface ChangeUserRoleCommand {
  role_id: string;
}

export interface AssignUserTerritoryCommand {
  territory_id: string;
}

export interface DeactivateUserCommand {
  reason?: string;
}

export interface UpdateRolePermissionsCommand {
  permissions: string[];
}

// API definition bridging Hooks to specific endpoints
export const coreApi = {
  // Projections (Queries)
  getUsers: (params?: any) => api.get<PaginatedResponse<User>>('/core/users', { params }),
  getUser: (id: string) => api.get<User>(`/core/users/${id}`),
  
  getRoles: () => api.get<Role[]>('/core/roles'),
  getRole: (id: string) => api.get<Role>(`/core/roles/${id}`),
  
  // Notice territory tree is fetched once as per constraints
  getTerritoryTree: () => api.get<Territory[]>('/core/territories/tree'),

  // Commands (Mutations)
  createUser: (cmd: CreateUserCommand) => api.post('/api/core/users/command', { type: 'CreateUser', payload: cmd }),
  changeUserRole: (userId: string, cmd: ChangeUserRoleCommand) => api.post(`/api/core/users/${userId}/command`, { type: 'ChangeUserRole', payload: cmd }),
  assignUserTerritory: (userId: string, cmd: AssignUserTerritoryCommand) => api.post(`/api/core/users/${userId}/command`, { type: 'AssignUserTerritory', payload: cmd }),
  deactivateUser: (userId: string, cmd: DeactivateUserCommand) => api.post(`/api/core/users/${userId}/command`, { type: 'DeactivateUser', payload: cmd }),
  
  updateRolePermissions: (roleId: string, cmd: UpdateRolePermissionsCommand) => api.post(`/api/core/roles/${roleId}/command`, { type: 'UpdateRolePermissions', payload: cmd }),
};
