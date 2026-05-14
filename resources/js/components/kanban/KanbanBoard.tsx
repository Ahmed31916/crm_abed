import React, { useState, useEffect } from 'react';
import { DragDropContext, Droppable, Draggable, DropResult } from '@hello-pangea/dnd';
import { KanbanColumn } from './KanbanColumn';
import { KanbanCard } from './KanbanCard';
import { router } from '@inertiajs/react';
import { toast } from '@/components/custom-toast';
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

interface KanbanData {
  [key: string]: {
    status: LeadStatus;
    leads: Lead[];
  };
}

interface KanbanBoardProps {
  initialData: KanbanData;
  leadStatuses: LeadStatus[];
  onLeadAction: (action: string, lead: Lead) => void;
  permissions: string[];
  searchTerm?: string;
  onDataUpdate?: (data: KanbanData) => void;
}

export const KanbanBoard: React.FC<KanbanBoardProps> = ({
  initialData,
  leadStatuses,
  onLeadAction,
  permissions,
  searchTerm = '',
  onDataUpdate
}) => {
  const { t } = useTranslation();
  const [kanbanData, setKanbanData] = useState<KanbanData>(initialData);

  useEffect(() => {
    setKanbanData(initialData);
  }, [initialData]);
  const [isLoading, setIsLoading] = useState(false);

  // Filter leads based on search term
  const filteredKanbanData = React.useMemo(() => {
    if (!searchTerm) return kanbanData;
    
    const filtered: KanbanData = {};
    Object.keys(kanbanData).forEach(statusId => {
      const column = kanbanData[statusId];
      const filteredLeads = column.leads.filter(lead =>
        lead.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        lead.email?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        lead.phone?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        lead.company?.toLowerCase().includes(searchTerm.toLowerCase())
      );
      
      filtered[statusId] = {
        ...column,
        leads: filteredLeads
      };
    });
    
    return filtered;
  }, [kanbanData, searchTerm]);

  const handleDragEnd = async (result: DropResult) => {
    const { destination, source, draggableId } = result;

    if (!destination) return;

    if (
      destination.droppableId === source.droppableId &&
      destination.index === source.index
    ) {
      return;
    }

    // Check if user has edit permission
    if (!permissions.includes('edit-leads')) {
      toast.error(t('Permission denied.'));
      return;
    }

    const sourceColumn = kanbanData[source.droppableId];
    const destColumn = kanbanData[destination.droppableId];
    const draggedLead = sourceColumn.leads.find(lead => lead.id.toString() === draggableId);

    if (!draggedLead) return;

    // Optimistic update
    const newKanbanData = { ...kanbanData };
    
    // Remove from source
    newKanbanData[source.droppableId] = {
      ...sourceColumn,
      leads: sourceColumn.leads.filter(lead => lead.id.toString() !== draggableId)
    };

    // Add to destination
    const updatedLead = {
      ...draggedLead,
      lead_status_id: parseInt(destination.droppableId),
      lead_status: destColumn.status
    };

    const destLeads = [...destColumn.leads];
    destLeads.splice(destination.index, 0, updatedLead);

    newKanbanData[destination.droppableId] = {
      ...destColumn,
      leads: destLeads
    };

    setKanbanData(newKanbanData);

    // Update backend
    try {
      setIsLoading(true);
      const response = await fetch(route('leads.update-status', draggedLead.id), {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({
          lead_status_id: parseInt(destination.droppableId)
        })
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || 'Failed to update lead status');
      }

      toast.success(data.message || t('Lead status updated successfully'));
      // Update the kanban data with the new lead data
      if (onDataUpdate && data.lead) {
        const updatedKanbanData = { ...kanbanData };
        // Remove lead from old column
        Object.keys(updatedKanbanData).forEach(statusId => {
          updatedKanbanData[statusId].leads = updatedKanbanData[statusId].leads.filter(
            (lead: Lead) => lead.id !== data.lead.id
          );
        });
        // Add lead to new column
        const newStatusId = data.lead.lead_status_id.toString();
        if (updatedKanbanData[newStatusId]) {
          updatedKanbanData[newStatusId].leads.push(data.lead);
        }
        onDataUpdate(updatedKanbanData);
      }
    } catch (error) {
      // Revert optimistic update on error
      setKanbanData(initialData);
      toast.error(error instanceof Error ? error.message : t('Failed to update lead status'));
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="h-full flex flex-col overflow-hidden">
      <DragDropContext onDragEnd={handleDragEnd}>
        <div className="flex gap-4 overflow-x-auto pb-4 kanban-scroll" style={{ height: 'calc(100vh - 280px)', width: '100%' }}>
          {leadStatuses.map((status) => (
            <KanbanColumn
              key={status.id}
              status={status}
              leads={filteredKanbanData[status.id]?.leads || []}
              onLeadAction={onLeadAction}
              permissions={permissions}
              isLoading={isLoading}
            />
          ))}
        </div>
      </DragDropContext>
    </div>
  );
};