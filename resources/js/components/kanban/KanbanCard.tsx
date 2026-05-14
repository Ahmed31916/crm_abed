import React from 'react';
import { Draggable } from '@hello-pangea/dnd';
import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { MoreHorizontal, Eye, Edit, Trash2, User, Building2, Users } from 'lucide-react';
import { useInitials } from '@/hooks/use-initials';
import { hasPermission } from '@/utils/authorization';
import { useTranslation } from 'react-i18next';

interface Lead {
  id: number;
  name: string;
  email: string;
  phone: string;
  company: string;
  value: string;
  lead_status_id: number;
  lead_status: {
    id: number;
    name: string;
    color: string;
  };
  lead_source: {
    id: number;
    name: string;
  };
  assigned_user: {
    id: number;
    name: string;
  };
  created_at: string;
}

interface KanbanCardProps {
  lead: Lead;
  index: number;
  onLeadAction: (action: string, lead: Lead) => void;
  permissions: string[];
  isLoading: boolean;
}

export const KanbanCard: React.FC<KanbanCardProps> = ({
  lead,
  index,
  onLeadAction,
  permissions,
  isLoading
}) => {
  const { t } = useTranslation();
  const getInitials = useInitials();

  const formatValue = (value: string) => {
    if (!value) return null;
    return `$${parseFloat(value).toFixed(2)}`;
  };

  const formatDate = (dateString: string) => {
    return window.appSettings?.formatDateTime(dateString, false) || new Date(dateString).toLocaleDateString();
  };

  return (
    <Draggable draggableId={lead.id.toString()} index={index} isDragDisabled={isLoading}>
      {(provided, snapshot) => (
        <Card
          ref={provided.innerRef}
          {...provided.draggableProps}
          {...provided.dragHandleProps}
          className={`p-3 cursor-grab active:cursor-grabbing transition-all duration-200 border border-gray-200 dark:border-gray-700 ${
            snapshot.isDragging
              ? 'shadow-2xl rotate-2 bg-white dark:bg-gray-900 scale-105 border-blue-300 dark:border-blue-600 z-50'
              : 'hover:shadow-lg hover:border-blue-200 dark:hover:border-blue-700 hover:scale-[1.02]'
          } ${isLoading ? 'opacity-50 cursor-not-allowed' : ''}`}
        >
          <div className="space-y-2">
            {/* Drag indicator */}
            <div className="flex justify-center mb-1">
              <div className="w-8 h-1 bg-gray-300 dark:bg-gray-600 rounded-full opacity-50 hover:opacity-100 transition-opacity" />
            </div>
            
            {/* Header with avatar and actions */}
            <div className="flex items-start justify-between">
              <div className="flex items-center gap-2 flex-1 min-w-0">
                <div className="flex-shrink-0 h-8 w-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 text-white flex items-center justify-center text-xs font-medium shadow-sm">
                  {getInitials(lead.name)}
                </div>
                <div className="flex-1 min-w-0">
                  <h4 className="font-medium text-gray-900 dark:text-white truncate text-sm hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                    {lead.name}
                  </h4>
                  <p className="text-xs text-gray-500 dark:text-gray-400 truncate">
                    {lead.email || t('No email')}
                  </p>
                </div>
              </div>
              
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button variant="ghost" size="sm" className="h-8 w-8 p-0 text-gray-400 hover:text-gray-600 dark:text-gray-400 dark:hover:text-gray-300">
                    <MoreHorizontal className="h-4 w-4" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-48 z-50" sideOffset={5}>
                  {hasPermission(permissions, 'view-leads') && (
                    <DropdownMenuItem onClick={() => onLeadAction('view', lead)}>
                      <Eye className="h-4 w-4 mr-2" />
                      <span>{t('View Lead')}</span>
                    </DropdownMenuItem>
                  )}
                  {hasPermission(permissions, 'edit-leads') && (
                    <DropdownMenuItem onClick={() => onLeadAction('edit', lead)}>
                      <Edit className="h-4 w-4 mr-2" />
                      <span>{t('Edit')}</span>
                    </DropdownMenuItem>
                  )}
                  {hasPermission(permissions, 'convert-leads') && (
                    <>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem onClick={() => onLeadAction('convert-to-account', lead)} className="text-green-600">
                        <Building2 className="h-4 w-4 mr-2" />
                        <span>{t('Convert to Account')}</span>
                      </DropdownMenuItem>
                      <DropdownMenuItem onClick={() => onLeadAction('convert-to-contact', lead)} className="text-blue-600">
                        <Users className="h-4 w-4 mr-2" />
                        <span>{t('Convert to Contact')}</span>
                      </DropdownMenuItem>
                    </>
                  )}
                  <DropdownMenuSeparator />
                  {hasPermission(permissions, 'delete-leads') && (
                    <DropdownMenuItem onClick={() => onLeadAction('delete', lead)} className="text-red-600">
                      <Trash2 className="h-4 w-4 mr-2" />
                      <span>{t('Delete')}</span>
                    </DropdownMenuItem>
                  )}
                </DropdownMenuContent>
              </DropdownMenu>
            </div>

            {/* Lead details */}
            <div className="space-y-1">
              {lead.company && (
                <div className="flex items-center gap-1">
                  <Building2 className="h-3 w-3 text-gray-400" />
                  <span className="text-xs text-gray-600 dark:text-gray-300 truncate">
                    {lead.company}
                  </span>
                </div>
              )}
              
              {lead.value && (
                <div className="flex items-center justify-between">
                  <span className="text-xs text-gray-500 dark:text-gray-400">{t('Value')}:</span>
                  <span className="text-xs font-medium text-green-600 dark:text-green-400">
                    {formatValue(lead.value)}
                  </span>
                </div>
              )}

              {lead.assigned_user && (
                <div className="flex items-center gap-1">
                  <User className="h-3 w-3 text-gray-400" />
                  <span className="text-xs text-gray-600 dark:text-gray-300 truncate">
                    {lead.assigned_user.name}
                  </span>
                </div>
              )}
            </div>

            {/* Footer with source and date */}
            <div className="flex items-center justify-between pt-1 border-t border-gray-100 dark:border-gray-700">
              {lead.lead_source && (
                <Badge variant="outline" className="text-xs px-1 py-0">
                  {lead.lead_source.name}
                </Badge>
              )}
              <span className="text-xs text-gray-400">
                {formatDate(lead.created_at)}
              </span>
            </div>
          </div>
        </Card>
      )}
    </Draggable>
  );
};