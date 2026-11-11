import { forwardRef, useMemo, type SelectHTMLAttributes } from 'react';
import { useCategories } from '@/api/storefront';
import { Select } from '@/components/ui';
import { buildCategoryTree, flattenTree } from '@/lib/categories';

interface CategorySelectProps extends Omit<SelectHTMLAttributes<HTMLSelectElement>, 'children'> {
  emptyLabel: string;
}

/** `<select>` of the nested category tree, indented by depth. Values are category ids. */
export const CategorySelect = forwardRef<HTMLSelectElement, CategorySelectProps>(function CategorySelect({ emptyLabel, ...rest }, ref) {
  const categories = useCategories();
  const options = useMemo(() => flattenTree(buildCategoryTree(categories.data ?? [])), [categories.data]);

  return (
    <Select ref={ref} disabled={categories.isLoading || rest.disabled} {...rest}>
      <option value="">{categories.isLoading ? 'Loading…' : emptyLabel}</option>
      {options.map(({ node, depth }) => (
        <option key={node.id} value={node.id}>
          {'   '.repeat(depth)}
          {depth > 0 ? '└ ' : ''}
          {node.name}
        </option>
      ))}
    </Select>
  );
});
