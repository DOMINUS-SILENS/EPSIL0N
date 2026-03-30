import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import {
  coreApi,
  CreateUserCommand,
  ChangeUserRoleCommand,
  AssignUserTerritoryCommand,
  UpdateRolePermissionsCommand
} from '../api/coreApi';

export const coreQueryKeys = {
  users: ['core', 'users'] as const,
  user: (id: string) => [...coreQueryKeys.users, id] as const,
  roles: ['core', 'roles'] as const,
  role: (id: string) => [...coreQueryKeys.roles, id] as const,
  territories: ['core', 'territories'] as const,
};

// ==========================================
// Users Hooks
// ==========================================
export const useUsers = (params?: any) => {
  return useQuery({
    queryKey: [...coreQueryKeys.users, params],
    queryFn: () => coreApi.getUsers(params),
    select: (res) => res.data,
  });
};

export const useUser = (id: string) => {
  return useQuery({
    queryKey: coreQueryKeys.user(id),
    queryFn: () => coreApi.getUser(id),
    select: (res) => res.data,
    enabled: !!id,
  });
};

export const useCreateUser = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (cmd: CreateUserCommand) => coreApi.createUser(cmd),
    onSuccess: () => {
      toast.success('User created system command dispatched');
      qc.invalidateQueries({ queryKey: coreQueryKeys.users });
    },
    onError: (err: any) => toast.error(err.response?.data?.message || 'Failed to create user'),
  });
};

export const useChangeUserRole = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ userId, cmd }: { userId: string, cmd: ChangeUserRoleCommand }) => 
      coreApi.changeUserRole(userId, cmd),
    onSuccess: (_, { userId }) => {
      toast.success('User role update command dispatched');
      qc.invalidateQueries({ queryKey: coreQueryKeys.user(userId) });
      qc.invalidateQueries({ queryKey: coreQueryKeys.users });
    },
  });
};

export const useAssignUserTerritory = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ userId, cmd }: { userId: string, cmd: AssignUserTerritoryCommand }) => 
      coreApi.assignUserTerritory(userId, cmd),
    onSuccess: (_, { userId }) => {
      toast.success('User territory update command dispatched');
      qc.invalidateQueries({ queryKey: coreQueryKeys.user(userId) });
      qc.invalidateQueries({ queryKey: coreQueryKeys.users });
    },
  });
};

// ==========================================
// Roles & Permissions Hooks
// ==========================================
export const useRoles = () => {
  return useQuery({
    queryKey: coreQueryKeys.roles,
    queryFn: () => coreApi.getRoles(),
    select: (res) => res.data,
  });
};

export const useUpdateRolePermissions = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ roleId, cmd }: { roleId: string, cmd: UpdateRolePermissionsCommand }) => 
      coreApi.updateRolePermissions(roleId, cmd),
    onSuccess: (_, { roleId }) => {
      toast.success('Role permissions updated successfully');
      qc.invalidateQueries({ queryKey: coreQueryKeys.role(roleId) });
      qc.invalidateQueries({ queryKey: coreQueryKeys.roles });
    },
  });
};

// ==========================================
// Territories Hooks
// ==========================================
export const useTerritoryTree = () => {
  return useQuery({
    queryKey: coreQueryKeys.territories,
    queryFn: () => coreApi.getTerritoryTree(),
    select: (res) => res.data,
    staleTime: Infinity, // Deterministic cache, fetch once
  });
};
