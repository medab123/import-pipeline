import { Globe, Folder, Server } from 'lucide-vue-next'

export const DOWNLOADER_TYPES = {
  http: {
    label: 'HTTP/HTTPS',
    description: 'Download from web URL',
    icon: Globe,
  },
  ftp: {
    label: 'FTP',
    description: 'File Transfer Protocol',
    icon: Server,
  },
  sftp: {
    label: 'SFTP',
    description: 'SSH File Transfer Protocol',
    icon: Server,
  },
  local: {
    label: 'Local File',
    description: 'Local file system',
    icon: Folder,
  },
} as const
