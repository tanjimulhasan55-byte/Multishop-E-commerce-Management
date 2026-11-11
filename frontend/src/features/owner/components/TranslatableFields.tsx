import { useState } from 'react';
import { Field, Input, Tabs, Textarea } from '@/components/ui';
import type { Locale, Translations } from '@/types/models';

interface TranslatableFieldsProps {
  /** API field names, e.g. `name` / `description` or `title` / `description`. */
  titleField: string;
  titleLabel: string;
  title: Translations;
  description: Translations;
  onTitle: (value: Translations) => void;
  onDescription: (value: Translations) => void;
  errorFor: (field: string) => string | undefined;
  titleRequired?: boolean;
  titlePlaceholder?: Partial<Record<Locale, string>>;
  descriptionPlaceholder?: Partial<Record<Locale, string>>;
  hint?: string;
}

const LOCALES: { value: Locale; label: string }[] = [
  { value: 'en', label: 'English' },
  { value: 'bn', label: 'বাংলা' },
];

/** English / Bangla tabbed inputs for a title + description pair. */
export function TranslatableFields({
  titleField,
  titleLabel,
  title,
  description,
  onTitle,
  onDescription,
  errorFor,
  titleRequired,
  titlePlaceholder,
  descriptionPlaceholder,
  hint,
}: TranslatableFieldsProps) {
  const [locale, setLocale] = useState<Locale>('en');
  const hasError = (l: Locale) => !!(errorFor(`${titleField}.${l}`) || errorFor(`description.${l}`));

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <Tabs
          variant="pill"
          label="Content language"
          value={locale}
          onChange={setLocale}
          items={LOCALES.map((l) => ({ value: l.value, label: hasError(l.value) ? `${l.label} •` : l.label }))}
        />
        {hint && <p className="text-[0.8125rem] text-muted">{hint}</p>}
      </div>
      <div role="tabpanel" className="space-y-4" lang={locale}>
        <Field
          label={`${titleLabel} (${locale === 'en' ? 'English' : 'Bangla'})`}
          required={titleRequired && locale === 'en'}
          optional={!titleRequired || locale === 'bn'}
          error={errorFor(`${titleField}.${locale}`) ?? (locale === 'en' ? errorFor(titleField) : undefined)}
        >
          <Input
            key={`t-${locale}`}
            value={title[locale] ?? ''}
            placeholder={titlePlaceholder?.[locale]}
            onChange={(e) => onTitle({ ...title, [locale]: e.target.value })}
          />
        </Field>
        <Field label={`Description (${locale === 'en' ? 'English' : 'Bangla'})`} optional error={errorFor(`description.${locale}`)}>
          <Textarea
            key={`d-${locale}`}
            rows={4}
            value={description[locale] ?? ''}
            placeholder={descriptionPlaceholder?.[locale]}
            onChange={(e) => onDescription({ ...description, [locale]: e.target.value })}
          />
        </Field>
      </div>
    </div>
  );
}
