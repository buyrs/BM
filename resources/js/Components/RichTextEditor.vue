<template>
  <div class="rich-text-editor">
    <div v-if="label" class="mb-2">
      <label class="block text-sm font-medium text-gray-700">{{ label }}</label>
    </div>
    
    <!-- Toolbar -->
    <div class="border border-gray-300 rounded-t-md bg-gray-50 px-3 py-2 flex flex-wrap gap-1">
      <button
        type="button"
        @click="editor.chain().focus().toggleBold().run()"
        :class="{ 'bg-gray-200': editor?.isActive('bold') }"
        class="p-2 rounded hover:bg-gray-200 transition-colors"
        title="Bold"
      >
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
          <path d="M6 4v12h4.5c2.5 0 4.5-1.5 4.5-4 0-1.5-.8-2.8-2-3.5 1.2-.7 2-2 2-3.5 0-2.5-2-4-4.5-4H6zm2 2h2.5c1.5 0 2.5.5 2.5 2s-1 2-2.5 2H8V6zm0 6h3c1.5 0 2.5.5 2.5 2s-1 2-2.5 2H8v-4z"/>
        </svg>
      </button>
      
      <button
        type="button"
        @click="editor.chain().focus().toggleItalic().run()"
        :class="{ 'bg-gray-200': editor?.isActive('italic') }"
        class="p-2 rounded hover:bg-gray-200 transition-colors"
        title="Italic"
      >
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
          <path d="M8 4h8v2h-2.5l-3 8H13v2H5v-2h2.5l3-8H8V4z"/>
        </svg>
      </button>
      
      <button
        type="button"
        @click="editor.chain().focus().toggleUnderline().run()"
        :class="{ 'bg-gray-200': editor?.isActive('underline') }"
        class="p-2 rounded hover:bg-gray-200 transition-colors"
        title="Underline"
      >
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
          <path d="M6 3v7c0 2.2 1.8 4 4 4s4-1.8 4-4V3h-2v7c0 1.1-.9 2-2 2s-2-.9-2-2V3H6zm-2 14h12v2H4v-2z"/>
        </svg>
      </button>
      
      <div class="w-px h-6 bg-gray-300 mx-1"></div>
      
      <button
        type="button"
        @click="editor.chain().focus().toggleBulletList().run()"
        :class="{ 'bg-gray-200': editor?.isActive('bulletList') }"
        class="p-2 rounded hover:bg-gray-200 transition-colors"
        title="Bullet List"
      >
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
          <path d="M4 6a2 2 0 100-4 2 2 0 000 4zM4 12a2 2 0 100-4 2 2 0 000 4zM4 18a2 2 0 100-4 2 2 0 000 4zM8 5h10v2H8V5zM8 11h10v2H8v-2zM8 17h10v2H8v-2z"/>
        </svg>
      </button>
      
      <button
        type="button"
        @click="editor.chain().focus().toggleOrderedList().run()"
        :class="{ 'bg-gray-200': editor?.isActive('orderedList') }"
        class="p-2 rounded hover:bg-gray-200 transition-colors"
        title="Numbered List"
      >
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
          <path d="M3 4h1v1H3V4zm0 3h1v1H3V7zm0 3h1v1H3v-1zm0 3h1v1H3v-1zM6 4h12v2H6V4zm0 4h12v2H6V8zm0 4h12v2H6v-2zm0 4h12v2H6v-2z"/>
        </svg>
      </button>
      
      <div class="w-px h-6 bg-gray-300 mx-1"></div>
      
      <button
        type="button"
        @click="editor.chain().focus().setHorizontalRule().run()"
        class="p-2 rounded hover:bg-gray-200 transition-colors"
        title="Horizontal Rule"
      >
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
          <path d="M3 10h14v2H3v-2z"/>
        </svg>
      </button>
      
      <div class="w-px h-6 bg-gray-300 mx-1"></div>
      
      <button
        type="button"
        @click="insertPlaceholder"
        class="p-2 rounded hover:bg-gray-200 transition-colors text-xs font-medium"
        title="Insert Placeholder"
      >
        {{}}
      </button>
    </div>
    
    <!-- Editor Content -->
    <div 
      ref="editorElement"
      class="border border-t-0 border-gray-300 rounded-b-md min-h-[200px] p-3 prose prose-sm max-w-none focus-within:ring-2 focus-within:ring-primary focus-within:border-transparent"
      :class="{ 'border-red-300': error }"
    ></div>
    
    <!-- Placeholder Dropdown -->
    <div v-if="showPlaceholders" class="relative">
      <div class="absolute top-0 left-0 z-10 bg-white border border-gray-300 rounded-md shadow-lg p-2 w-64">
        <div class="text-sm font-medium text-gray-700 mb-2">Insert Placeholder:</div>
        <div class="grid grid-cols-2 gap-1">
          <button
            v-for="placeholder in placeholders"
            :key="placeholder.key"
            @click="insertPlaceholderText(placeholder)"
            class="text-left p-2 text-xs hover:bg-gray-100 rounded font-mono"
          >
            {{ formatPlaceholder(placeholder.key) }}
          </button>
        </div>
        <button
          @click="showPlaceholders = false"
          class="mt-2 text-xs text-gray-500 hover:text-gray-700"
        >
          Close
        </button>
      </div>
    </div>
    
    <!-- Error Message -->
    <div v-if="error" class="mt-1 text-sm text-red-600">
      {{ error }}
    </div>
    
    <!-- Help Text -->
    <div v-if="help" class="mt-1 text-sm text-gray-500">
      {{ help }}
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import { Editor } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Placeholder from '@tiptap/extension-placeholder'
import Underline from '@tiptap/extension-underline'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  label: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: 'Start typing...'
  },
  error: {
    type: String,
    default: ''
  },
  help: {
    type: String,
    default: ''
  },
  disabled: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue'])

const editorElement = ref(null)
const editor = ref(null)
const showPlaceholders = ref(false)

const placeholders = [
  { key: 'tenant_name', description: 'Tenant Name' },
  { key: 'tenant_email', description: 'Tenant Email' },
  { key: 'tenant_phone', description: 'Tenant Phone' },
  { key: 'address', description: 'Property Address' },
  { key: 'start_date', description: 'Start Date' },
  { key: 'end_date', description: 'End Date' },
  { key: 'admin_name', description: 'Admin Name' },
  { key: 'admin_signature_date', description: 'Admin Signature Date' }
]

const insertPlaceholder = () => {
  showPlaceholders.value = !showPlaceholders.value
}

const formatPlaceholder = (key) => {
  return `{{${key}}}`
}

const insertPlaceholderText = (placeholder) => {
  editor.value.chain().focus().insertContent(`{{${placeholder.key}}}`).run()
  showPlaceholders.value = false
}

onMounted(() => {
  editor.value = new Editor({
    element: editorElement.value,
    extensions: [
      StarterKit,
      Underline,
      Placeholder.configure({
        placeholder: props.placeholder,
      }),
    ],
    content: props.modelValue,
    editable: !props.disabled,
    onUpdate: ({ editor }) => {
      emit('update:modelValue', editor.getHTML())
    },
  })
})

onBeforeUnmount(() => {
  if (editor.value) {
    editor.value.destroy()
  }
})

watch(() => props.modelValue, (newValue) => {
  if (editor.value && editor.value.getHTML() !== newValue) {
    editor.value.commands.setContent(newValue, false)
  }
})

watch(() => props.disabled, (newValue) => {
  if (editor.value) {
    editor.value.setEditable(!newValue)
  }
})
</script>

<style>
.ProseMirror {
  outline: none;
}

.ProseMirror p.is-editor-empty:first-child::before {
  color: #adb5bd;
  content: attr(data-placeholder);
  float: left;
  height: 0;
  pointer-events: none;
}

.ProseMirror ul, .ProseMirror ol {
  padding-left: 1.5rem;
}

.ProseMirror li {
  margin: 0.25rem 0;
}

.ProseMirror hr {
  border: none;
  border-top: 2px solid #e5e7eb;
  margin: 1rem 0;
}

.ProseMirror strong {
  font-weight: 600;
}

.ProseMirror em {
  font-style: italic;
}

.ProseMirror u {
  text-decoration: underline;
}
</style>