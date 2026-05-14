import React, { useState, useEffect } from 'react';
import { DragDropContext, Droppable, Draggable, DropResult } from '@hello-pangea/dnd';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { MoreHorizontal, Eye, Edit, Trash2, User, Building2, Users } from 'lucide-react';
import { router } from '@inertiajs/react';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { useInitials } from '@/hooks/use-initials';
import { hasPermission } from '@/utils/authorization';

interface KanbanItem {
  id: number;
  name: string;
  email?: string;
  phone?: string;
  company?: string;
  value?: string;
  amount?: string;
  close_date?: string;
  lead_status_id?: number;
  opportunity_stage_id?: number;
  lead_status?: { id: number; name: string; color: string; };
  opportunity_stage?: { id: number; name: string; color: string; };
  lead_source?: { id: number; name: string; };
  opportunity_source?: { id: number; name: string; };
  account?: { id: number; name: string; };
  assigned_user?: { id: number; name: string; };
  created_at: string;
  is_converted?: boolean;
}

interface KanbanStatus {
  id: number;
  name: string;
  color: string;
}

interface KanbanData {
  [key: string]: {
    status: KanbanStatus;
    leads: KanbanItem[];
  };
}

interface CommonKanbanBoardProps {
  initialData: KanbanData;
  statuses: KanbanStatus[];
  onItemAction: (action: string, item: KanbanItem) => void;
  permissions: string[];
  searchTerm?: string;
  onDataUpdate?: (data: KanbanData) => void;
  type: 'lead' | 'opportunity';
}

export const CommonKanbanBoard: React.FC<CommonKanbanBoardProps> = ({
  initialData,
  statuses,
  onItemAction,
  permissions,
  searchTerm = '',
  onDataUpdate,
  type
}) => {
  const { t } = useTranslation();
  const getInitials = useInitials();
  const [kanbanData, setKanbanData] = useState<KanbanData>(initialData);
  const [isLoading, setIsLoading] = useState(false);

  useEffect(() => {
    // Keep optimistic update
  }, [initialData]);

  const filteredKanbanData = React.useMemo(() => {
    if (!searchTerm) return kanbanData;
    
    const filtered: KanbanData = {};
    Object.keys(kanbanData).forEach(statusId => {
      const column = kanbanData[statusId];
      const filteredItems = column.leads.filter(item =>
        item.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        item.email?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        item.phone?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        item.company?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        item.account?.name?.toLowerCase().includes(searchTerm.toLowerCase())
      );
      
      filtered[statusId] = {
        ...column,
        leads: filteredItems
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
    const draggedItem = sourceColumn.leads.find(item => item.id.toString() === draggableId);

    if (!draggedItem) return;

    const newKanbanData = { ...kanbanData };
    
    newKanbanData[source.droppableId] = {
      ...sourceColumn,
      leads: sourceColumn.leads.filter(item => item.id.toString() !== draggableId)
    };

    const statusField = type === 'lead' ? 'lead_status_id' : 'opportunity_stage_id';
    const statusObj = type === 'lead' ? 'lead_status' : 'opportunity_stage';
    
    const updatedItem = {
      ...draggedItem,
      [statusField]: parseInt(destination.droppableId),
      [statusObj]: destColumn.status
    };

    const destLeads = [...destColumn.leads];
    destLeads.splice(destination.index, 0, updatedItem);

    newKanbanData[destination.droppableId] = {
      ...destColumn,
      leads: destLeads
    };

    setKanbanData(newKanbanData);

    try {
      setIsLoading(true);
      const endpoint = type === 'lead' ? 'leads.update-status' : 'opportunities.update-status';
      const payload = type === 'lead' 
        ? { lead_status_id: parseInt(destination.droppableId) }
        : { opportunity_stage_id: parseInt(destination.droppableId) };

      router.put(route(endpoint, draggedItem.id), payload, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          toast.success(t(`${type} status updated successfully`));
          if (onDataUpdate) {
            onDataUpdate(newKanbanData);
          }
        },
        onError: (errors) => {
          toast.error(typeof errors === 'string' ? errors : t(`Failed to update ${type} status`));
        },
        onFinish: () => {
          setIsLoading(false);
        }
      });
    } catch (error) {
      toast.error(error instanceof Error ? error.message : t(`Failed to update ${type} status`));
      setIsLoading(false);
    }
  };

  const renderItemActions = (item: KanbanItem) => {
    const viewPermission = type === 'lead' ? 'view-leads' : 'view-opportunities';
    const editPermission = type === 'lead' ? 'edit-leads' : 'edit-opportunities';
    const deletePermission = type === 'lead' ? 'delete-leads' : 'delete-opportunities';

    return (
      <DropdownMenuContent align="end" className="w-48 z-50" sideOffset={5}>
        {hasPermission(permissions, viewPermission) && (
          <DropdownMenuItem onClick={() => onItemAction('view', item)}>
            <Eye className="h-4 w-4 mr-2" />
            <span>{t(`View ${type}`)}</span>
          </DropdownMenuItem>
        )}
        {hasPermission(permissions, editPermission) && (
          <DropdownMenuItem onClick={() => onItemAction('edit', item)}>
            <Edit className="h-4 w-4 mr-2" />
            <span>{t('Edit')}</span>
          </DropdownMenuItem>
        )}
        {type === 'lead' && hasPermission(permissions, 'convert-leads') && !item.is_converted && (
          <>
            <DropdownMenuSeparator />
            <DropdownMenuItem onClick={() => onItemAction('convert-to-account', item)} className="text-green-600">
              <Building2 className="h-4 w-4 mr-2" />
              <span>{t('Convert to Account')}</span>
            </DropdownMenuItem>
            <DropdownMenuItem onClick={() => onItemAction('convert-to-contact', item)} className="text-blue-600">
              <Users className="h-4 w-4 mr-2" />
              <span>{t('Convert to Contact')}</span>
            </DropdownMenuItem>
          </>
        )}
        <DropdownMenuSeparator />
        {hasPermission(permissions, deletePermission) && (
          <DropdownMenuItem onClick={() => onItemAction('delete', item)} className="text-red-600">
            <Trash2 className="h-4 w-4 mr-2" />
            <span>{t('Delete')}</span>
          </DropdownMenuItem>
        )}
      </DropdownMenuContent>
    );
  };

  return (
    <div className="h-full flex flex-col overflow-hidden">
      <DragDropContext onDragEnd={handleDragEnd}>
        <div className="flex gap-4 overflow-x-auto pb-4 kanban-scroll" style={{ height: 'calc(100vh - 120px)', width: '100%' }}>
          {statuses.map((status) => (
            <div key={status.id} className="flex-shrink-0" style={{ minWidth: 'calc(20% - 16px)', width: 'calc(20% - 16px)' }}>
              <div className="bg-gray-100 rounded-lg h-full flex flex-col">
                <div className="p-3 border-b border-gray-200">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                      <div className="w-3 h-3 rounded-full shadow-sm" style={{ backgroundColor: status.color }} />
                      <h3 className="font-semibold text-sm text-gray-700">{status.name}</h3>
                    </div>
                    <span className="text-xs text-gray-500 bg-gray-200 px-2 py-1 rounded-full">
                      {filteredKanbanData[status.id]?.leads?.length || 0}
                    </span>
                  </div>
                </div>

                <Droppable droppableId={status.id.toString()}>
                  {(provided) => (
                    <div 
                      ref={provided.innerRef}
                      {...provided.droppableProps}
                      className="p-2 space-y-2 overflow-y-auto flex-1 column-scroll" 
                      style={{ maxHeight: 'calc(100vh - 190px)' }}
                    >
                      {(filteredKanbanData[status.id]?.leads || []).map((item, index) => (
                        <Draggable key={item.id} draggableId={item.id.toString()} index={index} isDragDisabled={isLoading}>
                          {(provided, snapshot) => (
                            <Card
                              ref={provided.innerRef}
                              {...provided.draggableProps}
                              {...provided.dragHandleProps}
                              className={`cursor-grab active:cursor-grabbing transition-all duration-200 border-l-4 ${
                                snapshot.isDragging
                                  ? 'shadow-2xl rotate-2 bg-white scale-105 border-blue-300 z-50'
                                  : 'hover:shadow-lg hover:border-blue-200 hover:scale-[1.02] border-gray-200'
                              } ${isLoading ? 'opacity-50 cursor-not-allowed' : ''}`}
                              style={{ borderLeftColor: type === 'lead' ? (item.lead_status?.color || '#6b7280') : (item.opportunity_stage?.color || '#6b7280') }}
                            >
                              <div className="p-3 space-y-3">
                                <div className="flex justify-center mb-1">
                                  <div className="w-8 h-1 bg-gray-300 rounded-full opacity-50 hover:opacity-100 transition-opacity" />
                                </div>
                                
                                <div className="flex items-start justify-between">
                                  <div className="flex items-center gap-3 flex-1 min-w-0">
                                    <div className="flex-shrink-0 h-10 w-10 rounded-full bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 text-white flex items-center justify-center text-sm font-semibold shadow-md ring-2 ring-white">
                                      {getInitials(item.name)}
                                    </div>
                                    <div className="flex-1 min-w-0">
                                      <h4 className="font-semibold text-gray-900 truncate text-sm hover:text-indigo-600 transition-colors leading-tight">
                                        {item.name}
                                      </h4>
                                      <p className="text-xs text-gray-500 truncate mt-0.5">
                                        {type === 'lead' ? (item.email || t('No email')) : (item.account?.name || 'No account')}
                                      </p>
                                    </div>
                                  </div>
                                  
                                  <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                      <Button variant="ghost" size="sm" className="h-7 w-7 p-0 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full">
                                        <MoreHorizontal className="h-4 w-4" />
                                      </Button>
                                    </DropdownMenuTrigger>
                                    {renderItemActions(item)}
                                  </DropdownMenu>
                                </div>

                                <div className="space-y-2">
                                  {type === 'lead' && item.company && (
                                    <div className="flex items-center gap-2 bg-gray-50 px-2 py-1 rounded-md">
                                      <Building2 className="h-3 w-3 text-indigo-500 flex-shrink-0" />
                                      <span className="text-xs text-gray-700 truncate font-medium">{item.company}</span>
                                    </div>
                                  )}
                                  
                                  {(item.value || item.amount) && (
                                    <div className="flex items-center justify-between bg-green-50 px-2 py-1 rounded-md">
                                      <span className="text-xs text-green-700 font-medium">{type === 'lead' ? t('Value') : t('Amount')}:</span>
                                      <span className="text-xs font-bold text-green-800">
                                        ${parseFloat(item.value || item.amount || '0').toFixed(2)}
                                      </span>
                                    </div>
                                  )}

                                  {item.assigned_user && (
                                    <div className="flex items-center gap-2 bg-blue-50 px-2 py-1 rounded-md">
                                      <User className="h-3 w-3 text-blue-500 flex-shrink-0" />
                                      <span className="text-xs text-blue-700 truncate font-medium">{item.assigned_user.name}</span>
                                    </div>
                                  )}
                                </div>

                                <div className="flex items-center justify-between pt-2 border-t border-gray-100">
                                  {(item.lead_source || item.opportunity_source) && (
                                    <span className="text-xs px-2 py-1 bg-purple-100 text-purple-700 rounded-full font-medium">
                                      {(item.lead_source || item.opportunity_source)?.name}
                                    </span>
                                  )}
                                  <span className="text-xs text-gray-500 font-medium">
                                    {type === 'opportunity' && item.close_date 
                                      ? new Date(item.close_date).toLocaleDateString()
                                      : window.appSettings?.formatDateTime(item.created_at, false) || new Date(item.created_at).toLocaleDateString()
                                    }
                                  </span>
                                </div>
                              </div>
                            </Card>
                          )}
                        </Draggable>
                      ))}
                      {provided.placeholder}
                      
                      {(filteredKanbanData[status.id]?.leads?.length || 0) === 0 && (
                        <div className="text-center py-8 text-gray-400">
                          <div className="w-12 h-12 mx-auto mb-3 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center shadow-sm">
                            <span className="text-lg">{type === 'lead' ? '👤' : '💼'}</span>
                          </div>
                          <p className="text-sm font-medium mb-1 text-gray-500">{t(`No ${type}s here`)}</p>
                          <p className="text-xs text-gray-400">{t(`Drag ${type}s here to update status`)}</p>
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