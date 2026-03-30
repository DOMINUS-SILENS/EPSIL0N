import { Hash, Settings2, Save, Undo2, Play } from 'lucide-react'

const MOCK_SEQUENCES = [
  { id: 'seq_inv', name: 'Customer Invoices', prefix: 'INV-', suffix: '', size: 5, current_next: 4091, active: true },
  { id: 'seq_po', name: 'Purchase Orders', prefix: 'PO-', suffix: '-26', size: 4, current_next: 802, active: true },
  { id: 'seq_emp', name: 'Employee IDs', prefix: 'EMP_', suffix: '', size: 3, current_next: 105, active: true },
  { id: 'seq_lead', name: 'CRM Leads', prefix: 'LD-', suffix: '', size: 6, current_next: 14002, active: false },
]

export function SequencesPage() {
  return (
    <div className="p-6 max-w-[1400px] mx-auto space-y-6">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-neutral-900 dark:text-neutral-100 flex items-center gap-2">
            <Hash className="h-6 w-6 text-primary" /> Document Sequences
          </h1>
          <p className="text-sm text-neutral-500 dark:text-neutral-400 mt-1">Configure global numbering prefixes, suffixes, and padding for all ERP records</p>
        </div>
      </div>

      <div className="bg-white dark:bg-neutral-900 shadow-sm border border-neutral-200 dark:border-neutral-800 rounded-lg overflow-hidden flex flex-col">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm whitespace-nowrap">
            <thead className="bg-neutral-50 dark:bg-neutral-800/50 text-neutral-500 dark:text-neutral-400">
              <tr>
                <th className="px-6 py-3 font-medium">Record Type</th>
                <th className="px-6 py-3 font-medium">Prefix</th>
                <th className="px-6 py-3 font-medium">Number Padding</th>
                <th className="px-6 py-3 font-medium">Suffix</th>
                <th className="px-6 py-3 font-medium">Next Number</th>
                <th className="px-6 py-3 font-medium text-center">Preview Example</th>
                <th className="px-6 py-3 font-medium text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
              {MOCK_SEQUENCES.map((seq) => {
                const paddedStr = seq.current_next.toString().padStart(seq.size, '0')
                const previewStr = `${seq.prefix}${paddedStr}${seq.suffix}`

                return (
                  <tr key={seq.id} className={`hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition-colors ${!seq.active ? 'opacity-60 grayscale' : ''}`}>
                    <td className="px-6 py-4">
                      <div className="font-bold text-neutral-900 dark:text-neutral-100 flex items-center gap-1.5">
                        <Settings2 className="h-4 w-4 text-neutral-400" />
                        {seq.name}
                      </div>
                      <span className="text-[10px] text-neutral-500 font-mono mt-0.5 block">{seq.id}</span>
                    </td>
                    <td className="px-6 py-4">
                      <input
                        type="text"
                        defaultValue={seq.prefix}
                        className="w-20 px-2 py-1 bg-white dark:bg-neutral-900 border border-neutral-300 dark:border-neutral-700 rounded text-sm text-center font-mono focus:ring-2 focus:ring-primary/50"
                      />
                    </td>
                    <td className="px-6 py-4">
                      <input
                        type="number"
                        defaultValue={seq.size}
                        className="w-16 px-2 py-1 bg-white dark:bg-neutral-900 border border-neutral-300 dark:border-neutral-700 rounded text-sm text-center focus:ring-2 focus:ring-primary/50"
                      />
                    </td>
                    <td className="px-6 py-4">
                      <input
                        type="text"
                        defaultValue={seq.suffix}
                        placeholder="(None)"
                        className="w-20 px-2 py-1 bg-white dark:bg-neutral-900 border border-neutral-300 dark:border-neutral-700 rounded text-sm text-center font-mono focus:ring-2 focus:ring-primary/50 placeholder:text-neutral-400"
                      />
                    </td>
                    <td className="px-6 py-4 font-mono font-bold text-primary">
                      {seq.current_next}
                    </td>
                    <td className="px-6 py-4 text-center">
                      <span className="inline-block bg-neutral-100 dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 font-mono px-3 py-1 rounded text-neutral-700 dark:text-neutral-300 font-bold tracking-widest text-xs">
                        {previewStr}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-right">
                      <div className="flex items-center justify-end gap-2">
                        <button className="p-1.5 text-neutral-400 hover:text-amber-600 transition-colors rounded hover:bg-amber-50 dark:hover:bg-amber-500/10" title="Reset Sequence">
                          <Undo2 className="h-4 w-4" />
                        </button>
                        <button className="p-1.5 text-neutral-400 hover:text-green-600 transition-colors rounded hover:bg-green-50 dark:hover:bg-green-500/10" title="Advance Sequence Manually">
                          <Play className="h-4 w-4" />
                        </button>
                        <button className="p-1.5 text-neutral-400 hover:text-primary transition-colors rounded hover:bg-primary/10" title="Save Settings">
                          <Save className="h-4 w-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                )
              })}
            </tbody>
          </table>
        </div>
      </div>
      <p className="text-xs text-neutral-500 italic">
        * Warning: Modifying sequence formats does not retroactively alter existing system records. Adjusting "Next Number" backward may cause database collision errors.
      </p>
    </div>
  )
}

export default SequencesPage;
