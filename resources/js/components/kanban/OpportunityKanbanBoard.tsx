import React, { useState, useEffect } from 'react';
import { DragDropContext, Droppable, Draggable, DropResult } from '@hello-pangea/dnd';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { MoreHorizontal, Eye, Edit, Trash2, User } from 'lucide-react';
import { router } from '@inertiajs/react';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { useInitials } from '@/hooks/use-initials';
import { hasPermission } from '@/utils/authorization';

interface Opportunity {
  id: number;
  name: string;
  amount: string;
  close_date: string;
  opportunity_stage_id: number;
  opportunity_stage: {
    id: number;
    name: string;
    color: string;
  };
  opportunity_source: {
    id: number;
    name: string;
  };
  account: {
    id: number;
    name: string;
  };
  assigned_user: {
    id: number;
    name: string;
  };
  created_at: string;
}

interface OpportunityStage {
  id: number;
  name: string;
  color: string;
}

interface KanbanData {
  [key: string]: {
    status: OpportunityStage;
    leads: Opportunity[];
  };
}

interface OpportunityKanbanBoardProps {
  initialData: KanbanData;
  opportunityStages: OpportunityStage[];
  onOpportunityAction: (action: string, opportunity: Opportunity) => void;
  permissions: string[];
  searchTerm?: string;
  onDataUpdate?: (data: KanbanData) => void;
}

export const OpportunityKanbanBoard: React.FC<OpportunityKanbanBoardProps> = ({
  initialData,
  opportunityStages,
  onOpportunityAction,
  permissions,
  searchTerm = '',
  onDataUpdate
}) => {
  const { t } = useTranslation();
  const getInitials = useInitials();
  const [kanbanData, setKanbanData] = useState<KanbanData>(initialData);
  const [isLoading, setIsLoading] = useState(false);

  useEffect(() => {
    setKanbanData(initialData);
  }, [initialData]);

  const filteredKanbanData = React.useMemo(() => {
    if (!searchTerm) return kanbanData;
    
    const filtered: KanbanData = {};
    Object.keys(kanbanData).forEach(stageId => {
      const column = kanbanData[stageId];
      const filteredOpportunities = column.leads.filter(opportunity =>
        opportunity.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        opportunity.account?.name?.toLowerCase().includes(searchTerm.toLowerCase())
      );
      
      filtered[stageId] = {
        ...column,
        leads: filteredOpportunities
      };
    });
    
    return filtered;
  }, [kanbanData, searchTerm]);

  const handleDragEnd = async (result: DropResult) => {
    const { destination, source, draggableId } = result;

    if (!destination || (destination.droppableId === source.droppableId && destination.index === source.index)) {
      return;
    }

    const sourceColumn = kanbanData[source.droppableId];
    const destColumn = kanbanData[destination.droppableId];
    const draggedOpportunity = sourceColumn.leads.find(opp => opp.id.toString() === draggableId);

    if (!draggedOpportunity) return;

    const newKanbanData = { ...kanbanData };
    
    newKanbanData[source.droppableId] = {
      ...sourceColumn,
      leads: sourceColumn.leads.filter(opp => opp.id.toString() !== draggableId)
    };

    const updatedOpportunity = {
      ...draggedOpportunity,
      opportunity_stage_id: parseInt(destination.droppableId),
      opportunity_stage: destColumn.status
    };

    const destLeads = [...destColumn.leads];
    destLeads.splice(destination.index, 0, updatedOpportunity);

    newKanbanData[destination.droppableId] = {
      ...destColumn,
      leads: destLeads
    };

    setKanbanData(newKanbanData);

    try {
      setIsLoading(true);
      const response = await fetch(route('opportunities.update-stage', draggedOpportunity.id), {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({
          opportunity_stage_id: parseInt(destination.droppableId)
        })
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || 'Failed to update opportunity stage');
      }

      toast.success(data.message || t('Opportunity stage updated successfully'));
      if (onDataUpdate && data.opportunity) {
        const updatedKanbanData = { ...kanbanData };
        Object.keys(updatedKanbanData).forEach(stageId => {
          updatedKanbanData[stageId].leads = updatedKanbanData[stageId].leads.filter(
            (opp: Opportunity) => opp.id !== data.opportunity.id
          );
        });
        const newStageId = data.opportunity.opportunity_stage_id.toString();
        if (updatedKanbanData[newStageId]) {
          updatedKanbanData[newStageId].leads.push(data.opportunity);
        }
        onDataUpdate(updatedKanbanData);
      }
    } catch (error) {
      setKanbanData(initialData);
      toast.error(error instanceof Error ? error.message : t('Failed to update opportunity stage'));
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="h-full flex flex-col overflow-hidden">
      <DragDropContext onDragEnd={handleDragEnd}>
        <div className="flex gap-4 overflow-x-auto pb-4 kanban-scroll" style={{ height: 'calc(100vh - 280px)', width: '100%' }}>
          {opportunityStages.map((stage) => (
            <div key={stage.id} className="flex-shrink-0" style={{ minWidth: 'calc(20% - 16px)', width: 'calc(20% - 16px)' }}>
              <div className="bg-gray-100 rounded-lg h-full flex flex-col">
                <div className="p-3 border-b border-gray-200">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                      <div className="w-3 h-3 rounded-full shadow-sm" style={{ backgroundColor: stage.color }} />
                      <h3 className="font-semibold text-sm text-gray-700">{stage.name}</h3>
                    </div>
                    <span className="text-xs text-gray-500 bg-gray-200 px-2 py-1 rounded-full">
                      {filteredKanbanData[stage.id]?.leads?.length || 0}
                    </span>
                  </div>
                </div>

                <Droppable droppableId={stage.id.toString()}>
                  {(provided) => (
                    <div 
                      ref={provided.innerRef}
                      {...provided.droppableProps}
                      className="p-2 space-y-2 overflow-y-auto flex-1 column-scroll" 
                      style={{ maxHeight: 'calc(100vh - 350px)' }}
                    >
                      {(filteredKanbanData[stage.id]?.leads || []).map((opportunity, index) => (
                        <Draggable key={opportunity.id} draggableId={opportunity.id.toString()} index={index} isDragDisabled={isLoading}>
                          {(provided, snapshot) => (
                            <Card
                              ref={provided.innerRef}
                              {...provided.draggableProps}
                              {...provided.dragHandleProps}
                              className={`p-3 cursor-grab active:cursor-grabbing transition-all duration-200 border border-gray-200 ${
                                snapshot.isDragging
                                  ? 'shadow-2xl rotate-2 bg-white scale-105 border-blue-300 z-50'
                                  : 'hover:shadow-lg hover:border-blue-200 hover:scale-[1.02]'
                              } ${isLoading ? 'opacity-50 cursor-not-allowed' : ''}`}
                            >
                              <div className="space-y-2">
                                <div className="flex justify-center mb-1">
                                  <div className="w-8 h-1 bg-gray-300 rounded-full opacity-50 hover:opacity-100 transition-opacity" />
                                </div>
                                
                                <div className="flex items-start justify-between">
                                  <div className="flex items-center gap-2 flex-1 min-w-0">
                                    <div className="flex-shrink-0 h-8 w-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 text-white flex items-center justify-center text-xs font-medium shadow-sm">
                                      {getInitials(opportunity.name)}
                                    </div>
                                    <div className="flex-1 min-w-0">
                                      <h4 className="font-medium text-gray-900 truncate text-sm hover:text-blue-600 transition-colors">
                                        {opportunity.name}
                                      </h4>
                                      <p className="text-xs text-gray-500 truncate">
                                        {opportunity.account?.name || 'No account'}
                                      </p>
                                    </div>
                                  </div>
                                  
                                  <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                      <Button variant="ghost" size="sm" className="h-8 w-8 p-0 text-gray-400 hover:text-gray-600">
                                        <MoreHorizontal className="h-4 w-4" />
                                      </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" className="w-48 z-50" sideOffset={5}>
                                      {hasPermission(permissions, 'view-opportunities') && (
                                        <DropdownMenuItem onClick={() => onOpportunityAction('view', opportunity)}>
                                          <Eye className="h-4 w-4 mr-2" />
                                          <span>{t('View Opportunity')}</span>
                                        </DropdownMenuItem>
                                      )}
                                      {hasPermission(permissions, 'edit-opportunities') && (
                                        <DropdownMenuItem onClick={() => onOpportunityAction('edit', opportunity)}>
                                          <Edit className="h-4 w-4 mr-2" />
                                          <span>{t('Edit')}</span>
                                        </DropdownMenuItem>
                                      )}
                                      {hasPermission(permissions, 'delete-opportunities') && (
                                        <DropdownMenuItem onClick={() => onOpportunityAction('delete', opportunity)} className="text-red-600">
                                          <Trash2 className="h-4 w-4 mr-2" />
                                          <span>{t('Delete')}</span>
                                        </DropdownMenuItem>
                                      )}
                                    </DropdownMenuContent>
                                  </DropdownMenu>
                                </div>

                                <div className="space-y-1">
                                  {opportunity.amount && (
                                    <div className="flex items-center justify-between">
                                      <span className="text-xs text-gray-500">{t('Amount')}:</span>
                                      <span className="text-xs font-medium text-green-600">
                                        ${parseFloat(opportunity.amount).toFixed(2)}
                                      </span>
                                    </div>
                                  )}

                                  {opportunity.assigned_user && (
                                    <div className="flex items-center gap-1">
                                      <User className="h-3 w-3 text-gray-400" />
                                      <span className="text-xs text-gray-600 truncate">
                                        {opportunity.assigned_user.name}
                                      </span>
                                    </div>
                                  )}
                                </div>

                                <div className="flex items-center justify-between pt-1 border-t border-gray-100">
                                  {opportunity.opportunity_source && (
                                    <span className="text-xs px-1 py-0 bg-gray-100 rounded text-gray-600">
                                      {opportunity.opportunity_source.name}
                                    </span>
                                  )}
                                  <span className="text-xs text-gray-400">
                                    {opportunity.close_date ? new Date(opportunity.close_date).toLocaleDateString() : 'No date'}
                                  </span>
                                </div>
                              </div>
                            </Card>
                          )}
                        </Draggable>
                      ))}
                      {provided.placeholder}
                      
                      {(filteredKanbanData[stage.id]?.leads?.length || 0) === 0 && (
                        <div className="text-center py-12 text-gray-500">
                          <div className="w-12 h-12 mx-auto mb-3 rounded-full bg-gray-100 flex items-center justify-center">
                            <span className="text-xs">💼</span>
                          </div>
                          <p className="text-sm font-medium mb-1">{t('No opportunities here')}</p>
                          <p className="text-xs opacity-75">{t('Drag opportunities here to update stage')}</p>
                        </div>
                      )}
                    </div>
                  )}
                </Droppable>
              </div>
            </div>
          ))}
        </div>
      </DragDropContext>
    </div>
  );
};