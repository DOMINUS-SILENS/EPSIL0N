import { useState } from 'react';
import { X, Upload, Camera } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';

export interface ChecklistItem {
  id: string;
  type: 'checkbox' | 'text' | 'number' | 'photo' | 'signature' | 'note';
  label: string;
  required?: boolean;
  options?: string[];
  value?: unknown;
}

export interface ChecklistProps {
  items: ChecklistItem[];
  title?: string;
  description?: string;
  onChange?: (items: ChecklistItem[]) => void;
  onSubmit?: (items: ChecklistItem[]) => void;
  readOnly?: boolean;
  className?: string;
}

function ChecklistItemComponent({
  item,
  onChange,
  readOnly,
}: {
  item: ChecklistItem;
  onChange: (value: unknown) => void;
  readOnly?: boolean;
}) {
  const [previewUrl, setPreviewUrl] = useState<string | null>(null);

  const handlePhotoUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      const url = URL.createObjectURL(file);
      setPreviewUrl(url);
      onChange(file);
    }
  };

  switch (item.type) {
    case 'checkbox':
      return (
        <div className="flex items-center space-x-2">
          <Checkbox
            id={item.id}
            checked={item.value as boolean}
            onCheckedChange={(checked: boolean) => onChange(checked)}
            disabled={readOnly}
          />
          <Label htmlFor={item.id} className="cursor-pointer">
            {item.label}
          </Label>
        </div>
      );

    case 'text':
      return (
        <div className="space-y-2">
          <Label htmlFor={item.id}>{item.label}</Label>
          <Input
            id={item.id}
            value={(item.value as string) || ''}
            onChange={(e) => onChange(e.target.value)}
            disabled={readOnly}
            placeholder="Enter text..."
          />
        </div>
      );

    case 'number':
      return (
        <div className="space-y-2">
          <Label htmlFor={item.id}>{item.label}</Label>
          <Input
            id={item.id}
            type="number"
            value={(item.value as number) || ''}
            onChange={(e) => onChange(Number(e.target.value))}
            disabled={readOnly}
            placeholder="0"
          />
        </div>
      );

    case 'photo':
      return (
        <div className="space-y-2">
          <Label>{item.label}</Label>
          <div className="flex items-center gap-4">
            {previewUrl ? (
              <div className="relative">
                <img src={previewUrl} alt="Preview" className="w-32 h-32 object-cover rounded-lg" />
                {!readOnly && (
                  <button
                    type="button"
                    onClick={() => {
                      setPreviewUrl(null);
                      onChange(null);
                    }}
                    className="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1"
                  >
                    <X className="w-3 h-3" />
                  </button>
                )}
              </div>
            ) : (
              <label className="flex flex-col items-center justify-center w-32 h-32 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-gray-400">
                <Camera className="w-8 h-8 text-gray-400" />
                <span className="text-xs text-gray-500 mt-1">Take photo</span>
                <input
                  type="file"
                  accept="image/*"
                  capture="environment"
                  className="hidden"
                  onChange={handlePhotoUpload}
                  disabled={readOnly}
                />
              </label>
            )}

            <label className="flex flex-col items-center justify-center w-32 h-32 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-gray-400">
              <Upload className="w-8 h-8 text-gray-400" />
              <span className="text-xs text-gray-500 mt-1">Upload</span>
              <input
                type="file"
                accept="image/*"
                className="hidden"
                onChange={handlePhotoUpload}
                disabled={readOnly || !!previewUrl}
              />
            </label>
          </div>
        </div>
      );

    case 'note':
      return (
        <div className="space-y-2">
          <Label htmlFor={item.id}>{item.label}</Label>
          <Textarea
            id={item.id}
            value={(item.value as string) || ''}
            onChange={(e) => onChange(e.target.value)}
            disabled={readOnly}
            placeholder="Enter notes..."
            rows={3}
          />
        </div>
      );

    default:
      return null;
  }
}

export function Checklist({
  items,
  title,
  description,
  onChange,
  onSubmit,
  readOnly = false,
  className = '',
}: ChecklistProps) {
  const [localItems, setLocalItems] = useState<ChecklistItem[]>(items);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleItemChange = (itemId: string, value: unknown) => {
    const updated = localItems.map((item) =>
      item.id === itemId ? { ...item, value } : item
    );
    setLocalItems(updated);
    onChange?.(updated);
  };

  const handleSubmit = async () => {
    const incompleteRequired = localItems.filter(
      (item) => item.required && !item.value
    );

    if (incompleteRequired.length > 0) {
      return;
    }

    setIsSubmitting(true);
    try {
      await onSubmit?.(localItems);
    } finally {
      setIsSubmitting(false);
    }
  };

  const progress = Math.round(
    (localItems.filter((item) => item.value).length / localItems.length) * 100
  );

  return (
    <div className={cn('space-y-6', className)}>
      {(title || description) && (
        <div className="space-y-2">
          {title && <h3 className="text-lg font-semibold">{title}</h3>}
          {description && <p className="text-sm text-gray-600">{description}</p>}
        </div>
      )}

      <div className="flex items-center gap-2">
        <div className="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
          <div
            className="h-full bg-green-500 transition-all duration-300"
            style={{ width: `${progress}%` }}
          />
        </div>
        <span className="text-sm font-medium">{progress}%</span>
      </div>

      <div className="space-y-4">
        {localItems.map((item, index) => (
          <div
            key={item.id}
            className={cn(
              'p-4 border rounded-lg',
              item.value ? 'border-green-300 bg-green-50' : 'border-gray-200',
              item.required && !item.value && 'border-yellow-300'
            )}
          >
            <div className="flex items-start gap-3">
              <div className="flex-shrink-0 w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center text-sm font-medium">
                {index + 1}
              </div>

              <div className="flex-1">
                <div className="flex items-center gap-2 mb-2">
                  <span className="font-medium">{item.label}</span>
                  {item.required && (
                    <span className="text-xs text-red-500">*Required</span>
                  )}
                </div>

                <ChecklistItemComponent
                  item={item}
                  onChange={(value) => handleItemChange(item.id, value)}
                  readOnly={readOnly}
                />
              </div>
              Lorem ipsum dolor sit amet consectetur adipisicing elit. Explicabo assumenda voluptate saepe maiores magnam, culpa non vitae voluptatum molestiae aut sit ducimus tempore voluptatem sequi autem adipisci nam eveniet tempora.
            </div>
          </div>
        ))}
      </div>

      {onSubmit && !readOnly && (
        <Button
          onClick={handleSubmit}
          disabled={isSubmitting || progress < 100}
          className="w-full"
        >
          {isSubmitting ? 'Submitting...' : 'Submit Checklist'}
        </Button>
      )}
    </div>
  );
}
