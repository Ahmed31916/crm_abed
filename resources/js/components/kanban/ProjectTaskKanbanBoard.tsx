import React, { useState, useEffect } from 'react';
import { DragDropContext, Droppable, Draggable, DropResult } from '@hello-pangea/dnd';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { MoreHorizontal, Eye, Edit, Trash2, User, Calendar, Plus } from 'lucide-react';
import { router } from '@inertiajs/react';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { hasPermission } from '@/utils/authorization';

interface ProjectTask {
  id: number;
  title: string;
  description?: string;
  task_status_id: string;
  priority: string;
  progress: number;
  due_date?: string;
  start_date?: string;
  assigned_user?: { id: number; name: string; };
  created_at: string;
}

interface KanbanStatus {
  id: string;
  name: string;
  color: string;
}

interface KanbanData {
  [key: string]: {
    status: KanbanStatus;
    tasks: ProjectTask[];
  };
}

interface ProjectTaskKanbanBoardProps {
  initialData: KanbanData;
  statuses: KanbanStatus[];
  onItemAction: (action: string, item: ProjectTask) => void;
  permissions: string[];
  projectId: number;
  onAddTask: (status: string) => void;
  onDataUpdate?: (data: KanbanData) => void;
}

export const ProjectTaskKanbanBoard: React.FC<ProjectTaskKanbanBoardProps> = ({
  initialData,
  statuses,
  onItemAction,
  permissions,
  projectId,
  onAddTask,
  onDataUpdate
}) => {
  const { t } = useTranslation();
  const [kanbanData, setKanbanData] = useState<KanbanData>(initialData);
  const [isLoading, setIsLoading] = useState(false);

  useEffect(() => {
    setKanbanData(initialData);
  }, [initialData]);

  const handleDragEnd = async (result: DropResult) => {
    const { destination, source, draggableId } = result;

    if (!destination || (destination.droppableId === source.droppableId && destination.index === source.index)) {
      return;
    }

    if (!hasPermission(permissions, 'edit-project-tasks')) {
      toast.error(t('Permission denied.'));
      return;
    }

    const sourceColumn = kanbanData[source.droppableId];
    const destColumn = kanbanData[destination.droppableId];
    const draggedItem = sourceColumn.tasks.find(item => item.id.toString() === draggableId);

    if (!draggedItem) return;

    const newKanbanData = { ...kanbanData };
    
    newKanbanData[source.droppableId] = {
      ...sourceColumn,
      tasks: sourceColumn.tasks.filter(item => item.id.toString() !== draggableId)
    };

    const updatedItem = {
      ...draggedItem,
      task_status_id: destination.droppableId
    };

    const destTasks = [...destColumn.tasks];
    destTasks.splice(destination.index, 0, updatedItem);

    newKanbanData[destination.droppableId] = {
      ...destColumn,
      tasks: destTasks
    };

    setKanbanData(newKanbanData);

    try {
      setIsLoading(true);
      
      router.put(route('project-tasks.update-status', draggedItem.id), 
        { task_status_id: destination.droppableId }, 
        {
          preserveState: true,
          preserveScroll: true,
          onSuccess: (page) => {
            if (page.props.flash.success) {
              toast.success(t(page.props.flash.success));
            } else if (page.props.flash.error) {
              toast.error(t(page.props.flash.error));
            }
            if (onDataUpdate) {
              onDataUpdate(newKanbanData);
            }
          },
          onError: (errors) => {
            if (typeof errors === 'string') {
              toast.error(errors);
            } else {
              toast.error(`Failed to update task status: ${Object.values(errors).join(', ')}`);
            }
            setKanbanData(initialData);
          }
        }
      );
    } catch (error) {
      toast.error(t('Failed to update task status'));
      setKanbanData(initialData);
    } finally {
      setIsLoading(false);
    }
  };

  const renderTaskActions = (task: ProjectTask) => {
    return (
      <DropdownMenuContent align="end" className="w-48 z-50" sideOffset={5}>
        {hasPermission(permissions, 'view-project-tasks') && (
          <DropdownMenuItem onClick={() => onItemAction('view', task)}>
            <Eye className="h-4 w-4 mr-2" />
            <span>{t('View Task')}</span>
          </DropdownMenuItem>
        )}
        {hasPermission(permissions, 'edit-project-tasks') && (
          <DropdownMenuItem onClick={() => onItemAction('edit', task)}>
            <Edit className="h-4 w-4 mr-2" />
            <span>{t('Edit')}</span>
          </DropdownMenuItem>
        )}
        <DropdownMenuSeparator />
        {hasPermission(permissions, 'delete-project-tasks') && (
          <DropdownMenuItem onClick={() => onItemAction('delete', task)} className="text-red-600">
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
        <div className="flex gap-4 overflow-x-auto pb-4 kanban-scroll h-full">
          {statuses.map((status) => (
            <div key={status.id} className="flex-shrink-0" style={{ minWidth: '380px', width: '380px' }}>
              <div className="bg-gray-100 rounded-lg flex flex-col" style={{ height: 'calc(100vh - 280px)' }}>
                <div className="p-3 border-b border-gray-200">
                  <div className="flex items-center justify-between mb-2">
                    <div className="flex items-center gap-2">
                      <div className="w-3 h-3 rounded-full shadow-sm" style={{ backgroundColor: status.color }} />
                      <h3 className="font-semibold text-sm text-gray-700">{status.name}</h3>
                    </div>
                    <span className="text-xs text-gray-500 bg-gray-200 px-2 py-1 rounded-full">
                      {kanbanData[status.id]?.tasks?.length || 0}
                    </span>
                  </div>
                  {hasPermission(permissions, 'create-project-tasks') && (
                    <Button
                      variant="ghost"
                      size="sm"
                      className="w-full text-xs h-7"
                      onClick={() => onAddTask(status.id)}
                    >
                      <Plus className="h-3 w-3 mr-1" />
                      {t('Add Task')}
                    </Button>
                  )}
                </div>

                <Droppable droppableId={status.id}>
                  {(provided) => (
                    <div 
                      ref={provided.innerRef}
                      {...provided.droppableProps}
                      className="p-2 space-y-2 overflow-y-auto flex-1 column-scroll" 
                      style={{ height: 'calc(100vh - 380px)' }}
                    >
                      {(kanbanData[status.id]?.tasks || []).map((task, index) => (
                        <Draggable key={task.id} draggableId={task.id.toString()} index={index} isDragDisabled={isLoading || !hasPermission(permissions, 'edit-project-tasks')}>
                          {(provided, snapshot) => (
                            <Card
                              ref={provided.innerRef}
                              {...provided.draggableProps}
                              {...provided.dragHandleProps}
                              className={`p-3 transition-all duration-200 border border-gray-200 ${
                                snapshot.isDragging
                                  ? 'shadow-2xl rotate-2 bg-white scale-105 border-blue-300 z-50'
                                  : 'hover:shadow-lg hover:border-blue-200 hover:scale-[1.02]'
                              } ${isLoading ? 'opacity-50 cursor-not-allowed' : ''} ${
                                hasPermission(permissions, 'edit-project-tasks') ? 'cursor-grab active:cursor-grabbing' : 'cursor-default'
                              }`}
                            >
                              <div className="space-y-2">
                                <div className="flex justify-center mb-2">
                                  <div className="w-8 h-1 bg-gray-300 rounded-full opacity-50 hover:opacity-100 transition-opacity" />
                                </div>
                                
                                <div className="flex items-start justify-between mb-3">
                                  <div className="flex items-center gap-2 flex-1 min-w-0">
                                    <div className="flex-shrink-0 h-8 w-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 text-white flex items-center justify-center text-xs font-medium shadow-sm">
                                      {task.title.charAt(0).toUpperCase()}
                                    </div>
                                    <div className="flex-1 min-w-0">
                                      <h4 className="font-medium text-gray-900 text-sm hover:text-blue-600 transition-colors line-clamp-2">
                                        {task.title}
                                      </h4>
                                      {task.description && (
                                        <p className="text-xs text-gray-500 truncate mt-1">
                                          {task.description}
                                        </p>
                                      )}
                                    </div>
                                  </div>
                                  
                                  <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                      <Button variant="ghost" size="sm" className="h-8 w-8 p-0 text-gray-400 hover:text-gray-600">
                                        <MoreHorizontal className="h-4 w-4" />
                                      </Button>
                                    </DropdownMenuTrigger>
                                    {renderTaskActions(task)}
                                  </DropdownMenu>
                                </div>

                                <div className="border border-gray-200 dark:border-gray-700 rounded-md p-3 mb-4">
                                  <div className="space-y-1 mb-2">
                                    <div className="flex items-center justify-between text-xs">
                                      <span className="text-gray-500">Progress:</span>
                                      <span className="font-medium">{task.progress}%</span>
                                    </div>
                                    <div className="w-full bg-gray-200 rounded-full h-1">
                                      <div 
                                        className="bg-blue-600 h-1 rounded-full transition-all duration-300" 
                                        style={{ width: `${task.progress}%` }}
                                      ></div>
                                    </div>
                                  </div>

                                  {task.assigned_user && (
                                    <div className="flex items-center gap-1 mb-2">
                                      <User className="h-3 w-3 text-gray-400" />
                                      <span className="text-xs text-gray-600 truncate">{task.assigned_user.name}</span>
                                    </div>
                                  )}

                                  {task.start_date && (
                                    <div className="flex items-center gap-1 mb-2">
                                      <Calendar className="h-3 w-3 text-green-400" />
                                      <span className="text-xs text-green-600">
                                        Start: {new Date(task.start_date).toLocaleDateString()}
                                      </span>
                                    </div>
                                  )}

                                  {task.due_date && (
                                    <div className="flex items-center gap-1 mb-2">
                                      <Calendar className="h-3 w-3 text-red-400" />
                                      <span className="text-xs text-red-600">
                                        Due: {new Date(task.due_date).toLocaleDateString()}
                                      </span>
                                    </div>
                                  )}

                                  <div className="flex flex-wrap gap-1">
                                    <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${
                                      task.priority === 'urgent' ? 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20' :
                                      task.priority === 'high' ? 'bg-orange-50 text-orange-700 ring-1 ring-inset ring-orange-600/20' :
                                      task.priority === 'medium' ? 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20' :
                                      'bg-gray-50 text-gray-700 ring-1 ring-inset ring-gray-600/20'
                                    }`}>
                                      {task.priority.charAt(0).toUpperCase() + task.priority.slice(1)}
                                    </span>
                                  </div>
                                </div>

                                <div className="text-xs text-gray-500 dark:text-gray-400 mb-4">
                                  {t('Created')}: {window.appSettings?.formatDateTime(task.created_at, false) || new Date(task.created_at).toLocaleDateString()}
                                </div>
                              </div>
                            </Card>
                          )}
                        </Draggable>
                      ))}
                      {provided.placeholder}
                      
                      {(kanbanData[status.id]?.tasks?.length || 0) === 0 && (
                        <div className="text-center py-12 text-gray-500">
                          <div className="w-12 h-12 mx-auto mb-3 rounded-full bg-gray-100 flex items-center justify-center">
                            <span className="text-lg">📋</span>
                          </div>
                          <p className="text-sm font-medium mb-1">{t('No tasks here')}</p>
                          <p className="text-xs opacity-75">{t('Drag tasks here or add new ones')}</p>
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