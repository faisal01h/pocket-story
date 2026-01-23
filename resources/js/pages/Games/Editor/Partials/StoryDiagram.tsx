import { useEffect, useRef, useState } from 'react';

export default function StoryDiagram({ nodes, onSelectNode }) {
    const containerRef = useRef(null);
    const [mermaid, setMermaid] = useState(null);
    const [status, setStatus] = useState('loading'); // 'loading' | 'ready' | 'error'

    useEffect(() => {
        const loadMermaid = async () => {
            try {
                // Using version 10.x for better stability
                const mod = await import('https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.esm.min.mjs');
                const m = mod.default;
                m.initialize({
                    startOnLoad: false,
                    theme: 'neutral',
                    securityLevel: 'loose',
                    flowchart: {
                        useMaxWidth: false,
                        htmlLabels: true,
                        curve: 'basis'
                    },
                    fontFamily: 'Inter, ui-sans-serif, system-ui'
                });
                setMermaid(m);
                setStatus('ready');
            } catch (err) {
                console.error('Failed to load Mermaid:', err);
                setStatus('error');
            }
        };
        loadMermaid();
    }, []);

    useEffect(() => {
        if (!containerRef.current || !nodes.length || !mermaid || status !== 'ready') return;

        const generateMermaidString = () => {
            let str = 'graph TD\n';

            nodes.forEach(node => {
                // Escape characters that Mermaid might choke on
                const title = (node.title || `Node ${node.id}`).replace(/["[\](){}]/g, '');
                const style = node.is_start_node ? ':::startNode' : '';
                str += `  n${node.id}["${title}"]${style}\n`;

                node.choices?.forEach(choice => {
                    if (choice.target_node_id) {
                        const label = (choice.label || '').replace(/["[\](){}]/g, '');
                        str += `  n${node.id} -- "${label}" --> n${choice.target_node_id}\n`;
                    }
                });
            });

            str += '\n  classDef startNode fill:#dcfce7,stroke:#22c55e,stroke-width:2px;\n';

            return str;
        };

        const renderDiagram = async () => {
            try {
                const id = `mermaid-render-${Math.floor(Math.random() * 10000)}`;
                const { svg } = await mermaid.render(id, generateMermaidString());

                if (containerRef.current) {
                    containerRef.current.innerHTML = svg;

                    // Add click listeners
                    nodes.forEach(node => {
                        const element = containerRef.current.querySelector(`#n${node.id}`);
                        if (element) {
                            element.style.cursor = 'pointer';
                            element.onclick = (e) => {
                                e.preventDefault();
                                onSelectNode(node);
                            };
                        }
                    });
                }
            } catch (err) {
                console.error('Mermaid render error:', err);
                setStatus('error');
            }
        };

        const timeoutId = setTimeout(renderDiagram, 100);
        return () => clearTimeout(timeoutId);
    }, [nodes, mermaid, status, onSelectNode]);

    if (status === 'loading') {
        return (
            <div className="flex flex-col items-center justify-center p-12 text-gray-400">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-500 mb-4"></div>
                <p className="text-sm">Loading Diagram Processor...</p>
            </div>
        );
    }

    if (status === 'error') {
        return (
            <div className="p-8 text-center text-red-500 bg-red-50 dark:bg-red-900/10 rounded-lg">
                <p className="font-semibold mb-2">Failed to render diagram</p>
                <p className="text-xs">Check browser console or ensure nodes are correctly linked.</p>
            </div>
        );
    }

    return (
        <div className="w-full flex-1 bg-white dark:bg-gray-900 overflow-auto p-4 border rounded-lg shadow-inner min-h-[400px]">
            <div ref={containerRef} className="flex justify-center" />
        </div>
    );
}
