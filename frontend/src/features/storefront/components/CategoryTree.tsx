import { useEffect, useState } from 'react';
import { ChevronRight } from 'lucide-react';
import { cn } from '@/lib/cn';
import type { CategoryNode } from '@/types/models';

interface CategoryTreeProps {
  nodes: CategoryNode[];
  selected: string;
  onSelect: (slug: string) => void;
  /** Ids to expand initially (ancestors of the selected category). */
  expandedIds?: Set<string>;
}

export function CategoryTree({ nodes, selected, onSelect, expandedIds }: CategoryTreeProps) {
  return (
    <ul role="tree" aria-label="Categories" className="space-y-0.5">
      <li role="treeitem" aria-selected={!selected}>
        <button
          type="button"
          onClick={() => onSelect('')}
          className={cn(
            'w-full rounded-lg px-2.5 py-1.5 text-left text-sm transition-colors duration-150',
            !selected ? 'bg-brand-50 font-medium text-brand-700' : 'text-ink-soft hover:bg-subtle hover:text-ink',
          )}
        >
          All categories
        </button>
      </li>
      {nodes.map((node) => (
        <TreeNode key={node.id} node={node} selected={selected} onSelect={onSelect} expandedIds={expandedIds} />
      ))}
    </ul>
  );
}

function TreeNode({ node, selected, onSelect, expandedIds }: { node: CategoryNode } & Omit<CategoryTreeProps, 'nodes'>) {
  const hasChildren = node.children.length > 0;
  const [open, setOpen] = useState(() => !!expandedIds?.has(node.id));
  const isSelected = selected === node.slug;

  useEffect(() => {
    if (expandedIds?.has(node.id)) setOpen(true);
  }, [expandedIds, node.id]);

  return (
    <li role="treeitem" aria-selected={isSelected} aria-expanded={hasChildren ? open : undefined}>
      <div className="flex items-center">
        <button
          type="button"
          onClick={() => onSelect(node.slug)}
          className={cn(
            'min-w-0 flex-1 truncate rounded-lg px-2.5 py-1.5 text-left text-sm transition-colors duration-150',
            isSelected ? 'bg-brand-50 font-medium text-brand-700' : 'text-ink-soft hover:bg-subtle hover:text-ink',
          )}
        >
          {node.name}
        </button>
        {hasChildren && (
          <button
            type="button"
            onClick={() => setOpen((o) => !o)}
            aria-label={`${open ? 'Collapse' : 'Expand'} ${node.name}`}
            className="flex size-7 shrink-0 items-center justify-center rounded-md text-muted hover:bg-subtle hover:text-ink"
          >
            <ChevronRight className={cn('size-4 transition-transform duration-200', open && 'rotate-90')} />
          </button>
        )}
      </div>
      {hasChildren && open && (
        <ul role="group" className="mt-0.5 ml-3 space-y-0.5 border-l border-line pl-2">
          {node.children.map((child) => (
            <TreeNode key={child.id} node={child} selected={selected} onSelect={onSelect} expandedIds={expandedIds} />
          ))}
        </ul>
      )}
    </li>
  );
}
