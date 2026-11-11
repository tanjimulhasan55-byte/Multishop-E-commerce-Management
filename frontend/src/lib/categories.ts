import type { Category, CategoryNode } from '@/types/models';

const byOrder = (a: Category, b: Category) => a.sort_order - b.sort_order || a.name.localeCompare(b.name);

/** Build a nested tree from the flat category list (parent_id links). */
export function buildCategoryTree(categories: Category[]): CategoryNode[] {
  const nodes = new Map<number, CategoryNode>();
  for (const c of categories) nodes.set(Number(c.id), { ...c, children: [] });

  const roots: CategoryNode[] = [];
  for (const node of nodes.values()) {
    const parent = node.parent_id !== null ? nodes.get(node.parent_id) : undefined;
    if (parent) parent.children.push(node);
    else roots.push(node);
  }

  const sort = (list: CategoryNode[]) => {
    list.sort(byOrder);
    list.forEach((n) => sort(n.children));
  };
  sort(roots);
  return roots;
}

/** Depth-first flattening — handy for indented `<select>` options. */
export function flattenTree(nodes: CategoryNode[], depth = 0): { node: CategoryNode; depth: number }[] {
  return nodes.flatMap((node) => [{ node, depth }, ...flattenTree(node.children, depth + 1)]);
}

/** Ids of every ancestor of `slug` (to auto-expand the tree). */
export function ancestorIds(categories: Category[], slug: string | null | undefined): Set<string> {
  const hit = categories.find((c) => c.slug === slug);
  if (!hit) return new Set();
  return new Set(hit.path.split('/').filter(Boolean));
}
