import { useEffect, useId, useMemo, useRef, useState } from 'react';
import { ImagePlus, RotateCcw, Trash2, Upload } from 'lucide-react';
import { cn } from '@/lib/cn';
import { Button } from './Button';

const ACCEPT = ['image/jpeg', 'image/png', 'image/webp'];
const MAX_BYTES = 2 * 1024 * 1024;

export interface ImagePickerValue {
  file: File | null;
  remove: boolean;
}

interface ImagePickerProps {
  label: string;
  /** URL of the currently saved image, if any. */
  currentUrl?: string | null;
  value: ImagePickerValue;
  onChange: (value: ImagePickerValue) => void;
  error?: string;
  hint?: string;
  /** Allow removing the saved image (sends remove_thumbnail=1). */
  removable?: boolean;
}

/** Thumbnail upload with live preview, client-side checks and remove/undo. */
export function ImagePicker({ label, currentUrl, value, onChange, error, hint, removable = true }: ImagePickerProps) {
  const input = useRef<HTMLInputElement>(null);
  const id = useId();
  const [localError, setLocalError] = useState<string | null>(null);
  const [dragging, setDragging] = useState(false);

  const preview = useMemo(() => (value.file ? URL.createObjectURL(value.file) : null), [value.file]);
  useEffect(() => () => {
    if (preview) URL.revokeObjectURL(preview);
  }, [preview]);

  const shown = preview ?? (value.remove ? null : currentUrl);

  const accept = (file: File | undefined) => {
    if (!file) return;
    if (!ACCEPT.includes(file.type)) return setLocalError('Use a JPG, PNG or WebP image.');
    if (file.size > MAX_BYTES) return setLocalError('Image must be 2 MB or smaller.');
    setLocalError(null);
    onChange({ file, remove: false });
  };

  const message = localError ?? error;

  return (
    <div className="flex flex-col gap-1.5">
      <span id={`${id}-label`} className="text-[0.8125rem] font-medium text-ink-soft">
        {label}
      </span>
      <div className="flex items-start gap-4">
        <button
          type="button"
          onClick={() => input.current?.click()}
          onDragOver={(e) => {
            e.preventDefault();
            setDragging(true);
          }}
          onDragLeave={() => setDragging(false)}
          onDrop={(e) => {
            e.preventDefault();
            setDragging(false);
            accept(e.dataTransfer.files[0]);
          }}
          aria-labelledby={`${id}-label`}
          aria-describedby={`${id}-desc`}
          className={cn(
            'relative flex size-28 shrink-0 items-center justify-center overflow-hidden rounded-xl border-2 border-dashed transition-colors duration-150',
            dragging ? 'border-brand-500 bg-brand-50' : message ? 'border-danger-500' : 'border-line-strong bg-canvas hover:border-brand-400',
          )}
        >
          {shown ? (
            <img src={shown} alt="" className="absolute inset-0 size-full object-cover" />
          ) : (
            <ImagePlus className="size-7 text-faint" aria-hidden />
          )}
        </button>
        <div className="flex min-w-0 flex-col gap-2 pt-1">
          <div className="flex flex-wrap gap-2">
            <Button size="sm" variant="outline" leadingIcon={<Upload className="size-3.5" />} onClick={() => input.current?.click()}>
              {shown ? 'Replace' : 'Upload'}
            </Button>
            {value.file && (
              <Button size="sm" variant="ghost" leadingIcon={<RotateCcw className="size-3.5" />} onClick={() => onChange({ file: null, remove: false })}>
                Undo
              </Button>
            )}
            {removable && !value.file && currentUrl && !value.remove && (
              <Button size="sm" variant="danger-ghost" leadingIcon={<Trash2 className="size-3.5" />} onClick={() => onChange({ file: null, remove: true })}>
                Remove
              </Button>
            )}
            {value.remove && (
              <Button size="sm" variant="ghost" leadingIcon={<RotateCcw className="size-3.5" />} onClick={() => onChange({ file: null, remove: false })}>
                Keep current
              </Button>
            )}
          </div>
          <p id={`${id}-desc`} className={cn('text-[0.8125rem]', message ? 'text-danger-600' : 'text-muted')} role={message ? 'alert' : undefined}>
            {message ?? (value.remove ? 'Image will be removed when you save.' : (hint ?? 'JPG, PNG or WebP · up to 2 MB · min 100×100px'))}
          </p>
        </div>
      </div>
      <input
        ref={input}
        type="file"
        accept={ACCEPT.join(',')}
        className="sr-only"
        tabIndex={-1}
        aria-hidden
        onChange={(e) => {
          accept(e.target.files?.[0]);
          e.target.value = '';
        }}
      />
    </div>
  );
}
