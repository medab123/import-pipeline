import { FileText, FileJson, FileCode, File } from 'lucide-vue-next'

export const READER_TYPES = {
  csv: {
    label: 'CSV',
    description: 'Comma-separated values format',
    icon: FileText,
  },
  json: {
    label: 'JSON',
    description: 'JavaScript Object Notation',
    icon: FileJson,
  },
  xml: {
    label: 'XML',
    description: 'Extensible Markup Language',
    icon: FileCode,
  },
  yaml: {
    label: 'YAML',
    description: 'YAML Ain\'t Markup Language',
    icon: File,
  },
} as const
