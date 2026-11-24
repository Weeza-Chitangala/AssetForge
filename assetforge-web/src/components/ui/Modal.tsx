import React, { useEffect } from 'react';
import { X } from 'lucide-react';
import { cn } from '../../lib/utils';

interface ModalProps {
  open: boolean;
  onClose: () => void;
  title: string;
  description?: string;
  size?: 'md' | 'lg' | 'xl';
  children: React.ReactNode;
}

const sizeMap = {
  md: 'max-w-lg',
  lg: 'max-w-2xl',
  xl: 'max-w-4xl',
};

export const Modal: React.FC<ModalProps> = ({
  open,
  onClose,
  title,
  description,
  size = 'lg',
  children,
}) => {
  useEffect(() => {
    if (!open) return;
    const handleKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') onClose();
    };
    window.addEventListener('keydown', handleKey);
    return () => window.removeEventListener('keydown', handleKey);
  }, [open, onClose]);

  if (!open) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
      <div
        className={cn(
          'w-full bg-white rounded-2xl shadow-xl border border-slate-200 max-h-[90vh] flex flex-col',
          sizeMap[size]
        )}
        role="dialog"
        aria-modal="true"
      >
        <div className="flex items-start justify-between px-6 py-4 border-b border-slate-100">
          <div>
            <h2 className="text-lg font-semibold text-slate-900">{title}</h2>
            {description && <p className="text-xs text-slate-500 mt-0.5">{description}</p>}
          </div>
          <button
            type="button"
            onClick={onClose}
            className="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
            aria-label="Close modal"
          >
            <X className="w-4 h-4" />
          </button>
        </div>
        <div className="p-6 overflow-y-auto">{children}</div>
      </div>
    </div>
  );
};
