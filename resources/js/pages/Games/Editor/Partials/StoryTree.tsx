import { useState } from 'react';
import { ChevronRight, ChevronDown, Circle, ArrowRight, AlertCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

export default function StoryTree({ nodes, selectedNodeId, onSelectNode }) {
    const startNodes = nodes.filter(n => n.is_start_node);

    // Build a map of node id -> node for quick lookup
    const nodeMap = new Map(nodes.map(n => [n.id, n]));

    // Track visited nodes to identify orphans later (optional)
    const reachableIds = new Set();

    const TreeNode = ({ nodeId, depth = 0, path = [] }) => {
        const node = nodeMap.get(nodeId);
        if (!node) return null;

        const isSelected = selectedNodeId === node.id;
        const isCycle = path.includes(nodeId);
        const [isExpanded, setIsExpanded] = useState(true);

        reachableIds.add(nodeId);

        const children = node.choices
            ?.filter(c => c.target_node_id)
            .map(c => ({
                targetId: parseInt(c.target_node_id),
                label: c.label
            })) || [];

        const hasChildren = children.length > 0;

        return (
            <div className="select-none">
                <div
                    className={cn(
                        "flex items-center gap-1 py-1 px-2 rounded-sm cursor-pointer transition-colors text-sm",
                        isSelected ? "bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 font-medium" : "hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300"
                    )}
                    style={{ marginLeft: `${depth * 12}px` }}
                    onClick={(e) => {
                        e.stopPropagation();
                        onSelectNode(node);
                    }}
                >
                    {hasChildren && !isCycle ? (
                        <button
                            onClick={(e) => {
                                e.stopPropagation();
                                setIsExpanded(!isExpanded);
                            }}
                            className="p-0.5 hover:bg-gray-200 dark:hover:bg-gray-700 rounded"
                        >
                            {isExpanded ? <ChevronDown className="h-3 w-3" /> : <ChevronRight className="h-3 w-3" />}
                        </button>
                    ) : (
                        <Circle className="h-2 w-2 mx-0.5 opacity-50" />
                    )}

                    <span className="truncate">{node.title || `Node #${node.id}`}</span>

                    {isCycle && (
                        <span className="text-[10px] text-orange-500 bg-orange-100 dark:bg-orange-900 px-1 rounded flex items-center gap-0.5 ml-auto">
                            <ArrowRight className="h-3 w-3" />
                            Loop
                        </span>
                    )}
                </div>

                {hasChildren && isExpanded && !isCycle && (
                    <div className="border-l border-gray-100 dark:border-gray-800 ml-[6px]">
                        {children.map((child, idx) => (
                            <TreeNode
                                key={`${nodeId}-${child.targetId}-${idx}`}
                                nodeId={child.targetId}
                                depth={depth + 1}
                                path={[...path, nodeId]}
                            />
                        ))}
                    </div>
                )}
            </div>
        );
    };

    return (
        <div className="space-y-4 pb-10">
            {startNodes.length > 0 ? (
                startNodes.map(node => (
                    <div key={node.id}>
                        <div className="text-xs font-semibold text-gray-500 mb-2 uppercase tracking-wider flex items-center gap-1">
                            Start Node
                        </div>
                        <TreeNode nodeId={node.id} />
                    </div>
                ))
            ) : (
                <div className="text-sm text-gray-500 italic p-4 text-center">
                    No start node defined.
                </div>
            )}

            {/* Optional: Show orphans logic would require a second pass or effect */}
        </div>
    );
}
