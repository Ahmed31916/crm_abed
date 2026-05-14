import { useState, useEffect } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { ArrowLeft, Plus } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { CrudFormModal } from '@/components/CrudFormModal';
import { toast } from '@/components/custom-toast';
import { hasPermission } from '@/utils/authorization';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';

// Helper functions for time scale calculations
const getTimelineConfig = (timeScale: string) => {
    const today = new Date();

    switch (timeScale) {
        case 'quarter-day':
            return {
                totalUnits: 96, // 24 hours * 4 quarters
                unitDuration: 6 * 60 * 60 * 1000, // 6 hours in ms
                startOffset: -24, // 24 quarters ago (6 days)
                label: 'Quarter'
            };
        case 'half-day':
            return {
                totalUnits: 48, // 24 days * 2 halves
                unitDuration: 12 * 60 * 60 * 1000, // 12 hours in ms
                startOffset: -24, // 24 half-days ago (12 days)
                label: 'Half Day'
            };
        case 'day':
            return {
                totalUnits: 60,
                unitDuration: 24 * 60 * 60 * 1000, // 1 day in ms
                startOffset: -15, // 15 days ago
                label: 'Day'
            };
        case 'week':
            return {
                totalUnits: 26, // 26 weeks
                unitDuration: 7 * 24 * 60 * 60 * 1000, // 1 week in ms
                startOffset: -8, // 8 weeks ago
                label: 'Week'
            };
        case 'month':
            return {
                totalUnits: 12, // 12 months
                unitDuration: 30 * 24 * 60 * 60 * 1000, // ~1 month in ms
                startOffset: -3, // 3 months ago
                label: 'Month'
            };
        default:
            return {
                totalUnits: 60,
                unitDuration: 24 * 60 * 60 * 1000,
                startOffset: -15,
                label: 'Day'
            };
    }
};

const calculateTaskPosition = (task: any, timeScale: string) => {
    const config = getTimelineConfig(timeScale);
    const today = new Date();
    const timelineStart = new Date(today.getTime() + (config.startOffset * config.unitDuration));

    const startDate = task.start_date ? new Date(task.start_date) : new Date();
    const endDate = task.due_date ? new Date(task.due_date) : new Date(Date.now() + 7 * 24 * 60 * 60 * 1000);

    const taskStart = Math.max(0, (startDate.getTime() - timelineStart.getTime()) / config.unitDuration);
    const taskWidth = Math.min(config.totalUnits - taskStart, (endDate.getTime() - Math.max(startDate.getTime(), timelineStart.getTime())) / config.unitDuration);

    const leftPercent = (taskStart / config.totalUnits) * 100;
    const widthPercent = (taskWidth / config.totalUnits) * 100;

    let duration;
    switch (timeScale) {
        case 'quarter-day':
        case 'half-day':
            duration = Math.ceil((endDate.getTime() - startDate.getTime()) / (1000 * 60 * 60));
            break;
        case 'week':
            duration = Math.ceil((endDate.getTime() - startDate.getTime()) / (1000 * 60 * 60 * 24 * 7));
            break;
        case 'month':
            duration = Math.ceil((endDate.getTime() - startDate.getTime()) / (1000 * 60 * 60 * 24 * 30));
            break;
        default:
            duration = Math.ceil((endDate.getTime() - startDate.getTime()) / (1000 * 60 * 60 * 24));
    }

    return {
        leftPercent,
        widthPercent,
        duration,
        startDate,
        endDate,
        timelineStart,
        totalUnits: config.totalUnits
    };
};

const getTodayPosition = (today: Date, totalUnits: number, timeScale: string) => {
    const config = getTimelineConfig(timeScale);
    const timelineStart = new Date(today.getTime() + (config.startOffset * config.unitDuration));
    return ((today.getTime() - timelineStart.getTime()) / config.unitDuration / totalUnits) * 100;
};

const getTimeScaleUnit = (timeScale: string) => {
    switch (timeScale) {
        case 'quarter-day':
            return 'hours';
        case 'half-day':
            return 'hours';
        case 'day':
            return 'days';
        case 'week':
            return 'weeks';
        case 'month':
            return 'months';
        default:
            return 'days';
    }
};

const GanttChart = ({ tasks, timeScale, onTaskClick }: { tasks: any[], timeScale: string, onTaskClick: (task: any) => void }) => {
    const { t } = useTranslation();
    const config = getTimelineConfig(timeScale);
    const today = new Date();
    const timelineStart = new Date(today.getTime() + (config.startOffset * config.unitDuration));

    const chartHeight = Math.max(400, tasks.length * 40 + 100);
    const unitWidth = timeScale === 'month' ? 120 : timeScale === 'week' ? 100 : 38;
    const chartWidth = config.totalUnits * unitWidth;

    return (
        <svg className="gantt" height={chartHeight} width={chartWidth} style={{ minWidth: chartWidth }}>
            {/* Grid Background */}
            <rect x="0" y="0" width={chartWidth} height={chartHeight} fill="#f9fafb" />

            {/* Vertical Grid Lines */}
            <g>
                {Array.from({ length: config.totalUnits + 1 }, (_, i) => (
                    <line key={i} x1={i * unitWidth} y1="0" x2={i * unitWidth} y2={chartHeight} stroke="#e5e7eb" strokeWidth="1" />
                ))}
            </g>

            {/* Horizontal Grid Lines */}
            <g>
                {tasks.map((_, index) => (
                    <line key={index} x1="0" y1={60 + (index + 1) * 40} x2={chartWidth} y2={60 + (index + 1) * 40} stroke="#e5e7eb" strokeWidth="1" />
                ))}
            </g>

            {/* Timeline Header */}
            <rect x="0" y="0" width={chartWidth} height="60" fill="white" stroke="#e5e7eb" strokeWidth="1" />

            {/* Timeline Labels */}
            <g className="date">
                {Array.from({ length: config.totalUnits }, (_, i) => {
                    const date = new Date(timelineStart.getTime() + (i * config.unitDuration));
                    const x = i * unitWidth + unitWidth / 2;
                    let upperLabel = '';
                    let lowerLabel = '';

                    switch (timeScale) {
                        case 'quarter-day':
                            const hours = Math.floor(date.getHours() / 6) * 6;
                            lowerLabel = hours.toString().padStart(2, '0');
                            if (date.getHours() === 0) {
                                upperLabel = `${date.getDate().toString().padStart(2, '0')} ${date.toLocaleDateString('en', { month: 'long' })}`;
                            }
                            break;
                        case 'half-day':
                            lowerLabel = date.getHours() < 12 ? '00' : '12';
                            if (date.getHours() === 0 && date.getDate() === 1) {
                                upperLabel = `${date.getDate().toString().padStart(2, '0')} ${date.toLocaleDateString('en', { month: 'long' })}`;
                            } else if (date.getHours() === 0) {
                                upperLabel = date.getDate().toString().padStart(2, '0');
                            }
                            break;
                        case 'day':
                            lowerLabel = date.getDate().toString().padStart(2, '0');
                            if (date.getDate() === 1) {
                                upperLabel = date.toLocaleDateString('en', { month: 'long' });
                            }
                            break;
                        case 'week':
                            const weekOfMonth = Math.ceil(date.getDate() / 7);
                            lowerLabel = date.getDate().toString().padStart(2, '0');
                            if (date.getDate() <= 7) {
                                upperLabel = date.toLocaleDateString('en', { month: 'long' });
                            }
                            break;
                        case 'month':
                            lowerLabel = date.toLocaleDateString('en', { month: 'long' });
                            if (date.getMonth() === 0) {
                                upperLabel = date.getFullYear().toString();
                            }
                            break;
                    }

                    return (
                        <g key={i}>
                            {upperLabel && (
                                <text x={x} y="20" fontSize="11" fill="#374151" textAnchor="middle" fontWeight="600">
                                    {upperLabel}
                                </text>
                            )}
                            <text x={x} y="45" fontSize="12" fill="#6b7280" textAnchor="middle">
                                {lowerLabel}
                            </text>
                        </g>
                    );
                })}
            </g>

            {/* Today Line */}
            <line
                x1={getTodayPosition(today, config.totalUnits, timeScale) * chartWidth / 100}
                y1="60"
                x2={getTodayPosition(today, config.totalUnits, timeScale) * chartWidth / 100}
                y2={chartHeight}
                stroke="#ef4444"
                strokeWidth="2"
            />

            {/* Task Bars */}
            <g className="bars">
                {tasks.map((task, index) => {
                    const { leftPercent, widthPercent } = calculateTaskPosition(task, timeScale);
                    const y = 68 + index * 40;
                    const x = (leftPercent / 100) * chartWidth;
                    const width = Math.max(20, (widthPercent / 100) * chartWidth);

                    // Get task status color from TaskStatus model or fallback
                    const barColor = task.task_status?.color || '#6b7280';

                    return (
                        <g key={task.id} className="bar-wrapper cursor-pointer" onClick={() => onTaskClick(task)}>
                            <rect
                                x={x}
                                y={y}
                                width={width}
                                height="20"
                                rx="3"
                                ry="3"
                                fill={barColor}
                                style={{ cursor: 'pointer' }}
                            />
                            {task.progress > 0 && (
                                <rect
                                    x={x}
                                    y={y}
                                    width={(width * task.progress) / 100}
                                    height="20"
                                    rx="3"
                                    ry="3"
                                    fill="rgba(0,0,0,0.2)"
                                />
                            )}
                            <text
                                x={x + width / 2}
                                y={y + 14}
                                fontSize="11"
                                fill="white"
                                fontWeight="500"
                                textAnchor="middle"
                            >
                                {width > 60 ? task.title.substring(0, 12) : ''}
                            </text>
                        </g>
                    );
                })}
            </g>
        </svg>
    );
};

export default function ProjectGantt() {
    const { t } = useTranslation();
    const { auth, project, tasks = [], taskStatuses = [], users = [], filters: pageFilters = {} } = usePage().props as any;
    const permissions = auth?.permissions || [];
    const isCompany = auth?.user?.type === 'company';

    const [isFormModalOpen, setIsFormModalOpen] = useState(false);
    const [currentItem, setCurrentItem] = useState<any>(null);
    const [formMode, setFormMode] = useState<'create' | 'edit'>('create');
    const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
    const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || 'all');
    const [selectedPriority, setSelectedPriority] = useState(pageFilters.priority || 'all');
    const [showFilters, setShowFilters] = useState(!!(pageFilters.status || pageFilters.priority || pageFilters.search));
    const [filteredTasks, setFilteredTasks] = useState(tasks);
    const [timeScale, setTimeScale] = useState('day');
    const [selectedTask, setSelectedTask] = useState<any>(null);

    const handleAddTask = () => {
        setCurrentItem(null);
        setFormMode('create');
        setIsFormModalOpen(true);
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters();
    };

    const applyFilters = () => {
        router.get(route('projects.gantt', project.id), {
            search: searchTerm || undefined,
            status: selectedStatus !== 'all' ? selectedStatus : undefined,
            priority: selectedPriority !== 'all' ? selectedPriority : undefined,
        }, { preserveState: true, preserveScroll: true });
    };

    const handleResetFilters = () => {
        setSearchTerm('');
        setSelectedStatus('all');
        setSelectedPriority('all');
        setShowFilters(false);
        router.get(route('projects.gantt', project.id), {}, { preserveState: true, preserveScroll: true });
    };

    const hasActiveFilters = () => {
        return searchTerm !== '' || selectedStatus !== 'all' || selectedPriority !== 'all';
    };

    const activeFilterCount = () => {
        return (searchTerm ? 1 : 0) + (selectedStatus !== 'all' ? 1 : 0) + (selectedPriority !== 'all' ? 1 : 0);
    };

    useEffect(() => {
        setFilteredTasks(tasks);
    }, [tasks]);

    const handleFormSubmit = (formData: any) => {
        if (formMode === 'create') {
            toast.loading(t('Creating task...'));

            const taskData = {
                ...formData,
                project_id: project.id
            };

            router.post(route('project-tasks.store'), taskData, {
                onSuccess: (page) => {
                    setIsFormModalOpen(false);
                    toast.dismiss();
                    if (page.props.flash.success) {
                        toast.success(t(page.props.flash.success));
                    }
                    router.reload();
                },
                onError: (errors) => {
                    toast.dismiss();
                    if (typeof errors === 'string') {
                        toast.error(errors);
                    } else {
                        toast.error(`Failed to create task: ${Object.values(errors).join(', ')}`);
                    }
                }
            });
        }
    };

    const pageActions = [
        {
            label: t('Back'),
            icon: <ArrowLeft className="h-4 w-4 mr-2" />,
            variant: 'outline',
            onClick: () => router.get(route('projects.show', project.id))
        }
    ];

    if (hasPermission(permissions, 'create-project-tasks')) {
        pageActions.unshift({
            label: t('Add Task'),
            icon: <Plus className="h-4 w-4 mr-2" />,
            variant: 'default',
            onClick: handleAddTask
        });
    }

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Projects'), href: route('projects.index') },
        { title: project.name, href: route('projects.show', project.id) },
        { title: t('Gantt View') }
    ];

    return (
        <PageTemplate
            title={`${project.name} - ${t('Gantt View')}`}
            url={`/projects/${project.id}/gantt`}
            actions={pageActions}
            breadcrumbs={breadcrumbs}
            noPadding
        >
            <style>{`
        .gantt-container::-webkit-scrollbar {
          height: 8px;
          width: 8px;
        }
        .gantt-container::-webkit-scrollbar-track {
          background: #f1f5f9;
          border-radius: 4px;
        }
        .gantt-container::-webkit-scrollbar-thumb {
          background: #cbd5e1;
          border-radius: 4px;
        }
        .gantt-container::-webkit-scrollbar-thumb:hover {
          background: #94a3b8;
        }
      `}</style>
            {/* Search and filters section */}
            <div className="bg-white dark:bg-gray-900 rounded-lg shadow mb-4 p-4">
                <SearchAndFilterBar
                    searchTerm={searchTerm}
                    onSearchChange={setSearchTerm}
                    onSearch={handleSearch}
                    filters={[
                        {
                            name: 'status',
                            label: t('Status'),
                            type: 'select',
                            value: selectedStatus,
                            onChange: setSelectedStatus,
                            options: [
                                { value: 'all', label: t('All Status') },
                                ...(tasks.reduce((acc: any[], task: any) => {
                                    if (task.task_status && !acc.find(item => item.value === task.task_status.id.toString())) {
                                        acc.push({ value: task.task_status.id.toString(), label: task.task_status.name });
                                    }
                                    return acc;
                                }, []))
                            ]
                        },
                        {
                            name: 'priority',
                            label: t('Priority'),
                            type: 'select',
                            value: selectedPriority,
                            onChange: setSelectedPriority,
                            options: [
                                { value: 'all', label: t('All Priority') },
                                { value: 'low', label: t('Low') },
                                { value: 'medium', label: t('Medium') },
                                { value: 'high', label: t('High') },
                                { value: 'urgent', label: t('Urgent') }
                            ]
                        }
                    ]}
                    showFilters={showFilters}
                    setShowFilters={setShowFilters}
                    hasActiveFilters={hasActiveFilters}
                    activeFilterCount={activeFilterCount}
                    onResetFilters={handleResetFilters}
                    onApplyFilters={applyFilters}
                    hidePerPage={true}
                    hideViewToggle={true}
                />
            </div>

            <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
                <div className="border-b border-gray-200 p-4">
                    <div className="flex justify-between items-center">
                        <h6 className="text-lg font-semibold text-gray-900 dark:text-white">{t('Gantt Chart')}</h6>
                        <div className="flex gap-1">
                            {[
                                { value: 'quarter-day', label: t('Quarter Day') },
                                { value: 'half-day', label: t('Half Day') },
                                { value: 'day', label: t('Day') },
                                { value: 'week', label: t('Week') },
                                { value: 'month', label: t('Month') }
                            ].map((scale) => (
                                <button
                                    key={scale.value}
                                    onClick={() => setTimeScale(scale.value)}
                                    className={`px-3 py-1 text-xs font-medium rounded transition-colors cursor-pointer ${timeScale === scale.value
                                        ? 'bg-blue-600 text-white'
                                        : 'bg-blue-100 text-blue-600 hover:bg-blue-200'
                                        }`}
                                >
                                    {scale.label}
                                </button>
                            ))}
                        </div>
                    </div>
                </div>

                <div className="gantt-container" style={{ overflow: 'scroll', width: 'calc(100vw - 300px)', height: 'calc(100vh - 300px)' }}>
                    <GanttChart tasks={filteredTasks} timeScale={timeScale} onTaskClick={setSelectedTask} />
                </div>
            </div>

            <CrudFormModal
                isOpen={isFormModalOpen}
                onClose={() => setIsFormModalOpen(false)}
                onSubmit={handleFormSubmit}
                formConfig={{
                    fields: [
                        { name: 'title', label: t('Task Title'), type: 'text', required: true },
                        { name: 'description', label: t('Description'), type: 'textarea' },
                        { name: 'start_date', label: t('Start Date'), type: 'date' },
                        { name: 'due_date', label: t('Due Date'), type: 'date' },
                        {
                            name: 'priority',
                            label: t('Priority'),
                            type: 'select',
                            options: [
                                { value: 'low', label: t('Low') },
                                { value: 'medium', label: t('Medium') },
                                { value: 'high', label: t('High') },
                                { value: 'urgent', label: t('Urgent') }
                            ],
                            defaultValue: 'medium'
                        },
                        {
                            name: 'task_status_id',
                            label: t('Status'),
                            type: 'select',
                            required: true,
                            emptyNote: { link: route('task-statuses.index'), linkText: t('Task Statuses') },
                            options: taskStatuses.map((status: any) => ({
                                value: status.id,
                                label: status.name
                            })),
                            defaultValue: taskStatuses.find((s: any) => s.name === 'To Do')?.id || taskStatuses[0]?.id
                        },
                        { name: 'estimated_hours', label: t('Estimated Hours'), type: 'number', step: '0.5' },
                        { name: 'progress', label: t('Progress (%)'), type: 'number', min: '0', max: '100', defaultValue: '0' },
                        ...(isCompany ? [{
                            name: 'assigned_to',
                            label: t('Assign To'),
                            type: 'select',
                            required: true,
                            emptyNote: { link: route('users.index'), linkText: t('Users') },
                            options: [...users.map((user: any) => ({ value: user.id, label: `${user.name} (${user.email})` }))]
                        }] : [])
                    ],
                    modalSize: 'lg'
                }}
                initialData={null}
                title={t('Add Task')}
                mode={formMode}
            />

            {/* Task Detail Modal */}
            {selectedTask && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" onClick={() => setSelectedTask(null)} style={{ backgroundColor: 'rgba(0, 0, 0, 0.5)' }}>
                    <div className="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4" onClick={(e) => e.stopPropagation()}>
                        <div className="flex items-center justify-between mb-4">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white">{selectedTask.title}</h3>
                            <button
                                onClick={() => setSelectedTask(null)}
                                className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 cursor-pointer"
                            >
                                ✕
                            </button>
                        </div>

                        <div className="space-y-3 text-sm">
                            <div className="flex justify-between">
                                <span className="text-gray-600 dark:text-gray-400">{t('Status')}:</span>
                                <span
                                    className="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset"
                                    style={{
                                        backgroundColor: `${selectedTask.task_status?.color}20`,
                                        color: selectedTask.task_status?.color,
                                        borderColor: `${selectedTask.task_status?.color}40`
                                    }}
                                >
                                    {selectedTask.task_status?.name}
                                </span>
                            </div>

                            <div className="flex justify-between">
                                <span className="text-gray-600 dark:text-gray-400">{t('Priority')}:</span>
                                <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${selectedTask.priority === 'urgent' ? 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20' :
                                    selectedTask.priority === 'high' ? 'bg-orange-50 text-orange-700 ring-1 ring-inset ring-orange-600/20' :
                                        selectedTask.priority === 'medium' ? 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20' :
                                            'bg-gray-50 text-gray-700 ring-1 ring-inset ring-gray-600/20'
                                    }`}>
                                    {t(selectedTask.priority.charAt(0).toUpperCase() + selectedTask.priority.slice(1))}
                                </span>
                            </div>

                            {selectedTask.assigned_user && (
                                <div className="flex justify-between">
                                    <span className="text-gray-600 dark:text-gray-400">{t('Assigned To')}:</span>
                                    <span className="text-gray-900 dark:text-white">{selectedTask.assigned_user.name}</span>
                                </div>
                            )}

                            <div className="flex justify-between">
                                <span className="text-gray-600 dark:text-gray-400">{t('Progress')}:</span>
                                <div className="flex items-center gap-2">
                                    <div className="w-20 bg-gray-200 rounded-full h-2">
                                        <div className="bg-blue-600 h-2 rounded-full" style={{ width: `${selectedTask.progress || 0}%` }}></div>
                                    </div>
                                    <span className="text-gray-900 dark:text-white">{selectedTask.progress || 0}%</span>
                                </div>
                            </div>

                            {selectedTask.start_date && (
                                <div className="flex justify-between">
                                    <span className="text-gray-600 dark:text-gray-400">{t('Start Date')}:</span>
                                    <span className="text-gray-900 dark:text-white">{window.appSettings?.formatDateTime(selectedTask.start_date, false) || new Date(selectedTask.start_date).toLocaleDateString()}</span>
                                </div>
                            )}

                            {selectedTask.due_date && (
                                <div className="flex justify-between">
                                    <span className="text-gray-600 dark:text-gray-400">{t('Due Date')}:</span>
                                    <span className="text-gray-900 dark:text-white">{window.appSettings?.formatDateTime(selectedTask.due_date, false) || new Date(selectedTask.due_date).toLocaleDateString()}</span>
                                </div>
                            )}

                            {selectedTask.description && (
                                <div>
                                    <span className="text-gray-600 dark:text-gray-400">{t('Description')}:</span>
                                    <p className="text-gray-900 dark:text-white mt-1">{selectedTask.description}</p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            )}
        </PageTemplate>
    );
}
