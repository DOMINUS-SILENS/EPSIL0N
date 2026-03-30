import { useState, useCallback } from 'react'
import { CellChange } from './types'

interface UseUndoRedoProps {
  maxHistory?: number
  onSave?: (changes: CellChange[]) => void
}

export function useUndoRedo({ maxHistory = 50, onSave }: UseUndoRedoProps) {
  const [history, setHistory] = useState<CellChange[]>([])
  const [pointer, setPointer] = useState(-1)

  const push = useCallback((change: CellChange) => {
    setHistory(prev => {
      const newHistory = [...prev.slice(0, pointer + 1), change]
      if (newHistory.length > maxHistory) {
        newHistory.shift()
      }
      return newHistory
    })
    setPointer(prev => Math.min(prev + 1, maxHistory - 1))
  }, [pointer, maxHistory])

  const undo = useCallback(() => {
    if (pointer >= 0) {
      const change = history[pointer]
      if (change && onSave) {
        onSave([{
          ...change,
          newValue: change.oldValue,
          oldValue: change.newValue,
        }])
      }
      setPointer(prev => prev - 1)
    }
  }, [pointer, history, onSave])

  const redo = useCallback(() => {
    if (pointer < history.length - 1) {
      const change = history[pointer + 1]
      if (change && onSave) {
        onSave([change])
      }
      setPointer(prev => prev + 1)
    }
  }, [pointer, history, onSave])

  return {
    undo,
    redo,
    push,
    canUndo: pointer >= 0,
    canRedo: pointer < history.length - 1,
    history,
    pointer,
  }
}
