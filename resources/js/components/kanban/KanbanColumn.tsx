import React from 'react';
import { Droppable } from '@hello-pangea/dnd';
import { KanbanCard } from './KanbanCard';
import { Badge } from '@/components/ui/badge';
import { User } from 'lucide-react';
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

interface LeadStatus {
  id: number;
  name: string;
  color: string;
}

interface KanbanColumnProps {
  status: LeadStatus;
  leads: Lead[];
  onLeadAction: (action: string, lead: Lead) => void;
  permissions: string[];
  isLoading: boolean;
}

export const KanbanColumn: React.FC<KanbanColumnProps> = ({
  status,
  leads,
  onLeadAction,
  permissions,
  isLoading
}) => {
  const { t } = useTranslation();

  return (
    <div 
      className="flex-shrink-0"
      style={{ minWidth: 'calc(20% - 16px)', width: 'calc(20% - 16px)' }}
    >
      <div className="bg-gray-100 rounded-lg h-full flex flex-col">
        {/* Column Header */}
        <div className="p-3 border-b border-gray-200">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <div
                className="w-3 h-3 rounded-full shadow-sm"
                style={{ backgroundColor: status.color }}
              />
              <h3 className="font-semibold text-sm text-gray-700">{status.name}</h3>
            </div>
            <span className="text-xs text-gray-500 bg-gray-200 px-2 py-1 rounded-full">
              {leads.length}
            </span>
          </div>
        </div>

        {/* Column Content */}
        <Droppable droppableId={status.id.toString()}>
          {(provided, snapshot) => (
            <div 
              ref={provided.innerRef}
              {...provided.droppableProps}
              className="p-2 space-y-2 overflow-y-auto flex-1 column-scroll" 
              style={{ maxHeight: 'calc(100vh - 350px)' }}
            >
              {leads.map((lead, index) => (
                <KanbanCard
                  key={lead.id}
                  lead={lead}
                  index={index}
                  onLeadAction={onLeadAction}
                  permissions={permissions}
                  isLoading={isLoading}
                />
              ))}
              {provided.placeholder}
              
              {leads.length === 0 && (
                <div className="text-center py-12 text-gray-500 dark:text-gray-400">
                  <div className="w-12 h-12 mx-auto mb-3 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                    <User className="h-6 w-6 text-gray-400" />
                  </div>
                  <p className="text-sm font-medium mb-1">{t('No leads here')}</p>
                  <p className="text-xs opacity-75">{t('Drag leads here to update status')}</p>
                </div>
              )}
            </div>
          )}
        </Droppable>
      </div>
    </div>
  );
};