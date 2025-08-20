import { useState, useRef, useEffect } from 'react'
import { Button } from '@/components/ui/button.jsx'
import { Input } from '@/components/ui/input.jsx'
import { Card, CardContent } from '@/components/ui/card.jsx'
import { Badge } from '@/components/ui/badge.jsx'
import { Send, Bot, User, Sparkles, CheckCircle, Copy, ExternalLink, Loader2 } from 'lucide-react'
import './App.css'

function App() {
  const [messages, setMessages] = useState([])
  const [inputValue, setInputValue] = useState('')
  const [isTyping, setIsTyping] = useState(false)
  const [sessionId, setSessionId] = useState(null)
  const [isConnecting, setIsConnecting] = useState(true)
  const messagesEndRef = useRef(null)
  const inputRef = useRef(null)

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" })
  }

  useEffect(() => {
    scrollToBottom()
  }, [messages])

  useEffect(() => {
    // Start conversation when component mounts
    startConversation()
  }, [])

  const startConversation = async () => {
    try {
      setIsConnecting(true)
      const response = await fetch('/api/conversation/start', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        }
      })
      
      if (response.ok) {
        const data = await response.json()
        setSessionId(data.session_id)
        addMessage('bot', data.message)
      } else {
        addMessage('bot', "Sorry, I'm having trouble connecting. Please refresh the page and try again.")
      }
    } catch (error) {
      console.error('Error starting conversation:', error)
      addMessage('bot', "Sorry, I'm having trouble connecting. Please refresh the page and try again.")
    } finally {
      setIsConnecting(false)
    }
  }

  const addMessage = (type, content, extra = {}) => {
    const newMessage = {
      id: Date.now(),
      type,
      content,
      timestamp: new Date(),
      ...extra
    }
    setMessages(prev => [...prev, newMessage])
  }

  const simulateTyping = (callback) => {
    setIsTyping(true)
    setTimeout(() => {
      setIsTyping(false)
      callback()
    }, 800 + Math.random() * 800)
  }

  const handleSendMessage = async () => {
    if (!inputValue.trim() || !sessionId || isTyping) return

    const userMessage = inputValue.trim()
    addMessage('user', userMessage)
    setInputValue('')

    try {
      setIsTyping(true)
      const response = await fetch(`/api/conversation/${sessionId}/message`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ message: userMessage })
      })

      if (response.ok) {
        const data = await response.json()
        
        setTimeout(() => {
          setIsTyping(false)
          addMessage('bot', data.message)
          
          // If there's an embed code, add it as a separate message
          if (data.embed_code) {
            setTimeout(() => {
              addMessage('embed', data.embed_code, { form_id: data.form_id })
            }, 500)
          }
        }, 1000)
      } else {
        setIsTyping(false)
        addMessage('bot', "Sorry, I encountered an error. Please try again.")
      }
    } catch (error) {
      console.error('Error sending message:', error)
      setIsTyping(false)
      addMessage('bot', "Sorry, I'm having trouble processing your message. Please try again.")
    }
  }

  const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text).then(() => {
      // Could add a toast notification here
    })
  }

  const handleKeyPress = (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault()
      handleSendMessage()
    }
  }

  if (isConnecting) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-purple-600 via-blue-600 to-indigo-700 flex items-center justify-center">
        <Card className="bg-white/95 backdrop-blur-sm">
          <CardContent className="p-8 text-center">
            <Loader2 className="w-8 h-8 animate-spin mx-auto mb-4 text-purple-600" />
            <h2 className="text-xl font-semibold mb-2">Starting TiD Forms Assistant</h2>
            <p className="text-gray-600">Preparing your conversational form builder...</p>
          </CardContent>
        </Card>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-purple-600 via-blue-600 to-indigo-700">
      {/* Header */}
      <div className="bg-white/10 backdrop-blur-md border-b border-white/20">
        <div className="max-w-4xl mx-auto px-4 py-4">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-gradient-to-r from-purple-400 to-blue-400 rounded-lg flex items-center justify-center">
              <Sparkles className="w-6 h-6 text-white" />
            </div>
            <div>
              <h1 className="text-xl font-bold text-white">TiD Forms</h1>
              <p className="text-white/70 text-sm">Conversational Form Builder - Release 2</p>
            </div>
            <div className="ml-auto">
              <Badge variant="secondary" className="bg-green-100 text-green-800">
                Connected
              </Badge>
            </div>
          </div>
        </div>
      </div>

      {/* Chat Container */}
      <div className="max-w-4xl mx-auto px-4 py-6 h-[calc(100vh-120px)] flex flex-col">
        {/* Messages */}
        <div className="flex-1 overflow-y-auto space-y-4 mb-4">
          {messages.map((message) => (
            <div
              key={message.id}
              className={`flex gap-3 ${message.type === 'user' ? 'justify-end' : 'justify-start'}`}
            >
              {message.type === 'bot' && (
                <div className="w-8 h-8 bg-gradient-to-r from-purple-400 to-blue-400 rounded-full flex items-center justify-center flex-shrink-0">
                  <Bot className="w-5 h-5 text-white" />
                </div>
              )}
              
              <div className={`max-w-[80%] ${message.type === 'user' ? 'order-1' : ''}`}>
                {message.type === 'embed' ? (
                  <Card className="bg-gray-900 border-gray-700">
                    <CardContent className="p-4">
                      <div className="flex items-center justify-between mb-2">
                        <Badge variant="secondary" className="bg-green-100 text-green-800">
                          Embed Code
                        </Badge>
                        <div className="flex gap-2">
                          <Button
                            size="sm"
                            variant="ghost"
                            onClick={() => copyToClipboard(message.content)}
                            className="h-6 px-2 text-gray-400 hover:text-white"
                          >
                            <Copy className="w-3 h-3" />
                          </Button>
                          {message.form_id && (
                            <Button
                              size="sm"
                              variant="ghost"
                              onClick={() => window.open(`/form/${message.form_id}/preview`, '_blank')}
                              className="h-6 px-2 text-gray-400 hover:text-white"
                            >
                              <ExternalLink className="w-3 h-3" />
                            </Button>
                          )}
                        </div>
                      </div>
                      <code className="text-sm text-green-400 font-mono break-all block">
                        {message.content}
                      </code>
                    </CardContent>
                  </Card>
                ) : (
                  <Card className={`${
                    message.type === 'user' 
                      ? 'bg-gradient-to-r from-purple-500 to-blue-500 text-white' 
                      : 'bg-white/95 backdrop-blur-sm'
                  }`}>
                    <CardContent className="p-4">
                      <div className="whitespace-pre-wrap text-sm leading-relaxed">
                        {message.content}
                      </div>
                      <div className={`text-xs mt-2 ${
                        message.type === 'user' ? 'text-white/70' : 'text-gray-500'
                      }`}>
                        {message.timestamp.toLocaleTimeString()}
                      </div>
                    </CardContent>
                  </Card>
                )}
              </div>

              {message.type === 'user' && (
                <div className="w-8 h-8 bg-gradient-to-r from-green-400 to-blue-400 rounded-full flex items-center justify-center flex-shrink-0">
                  <User className="w-5 h-5 text-white" />
                </div>
              )}
            </div>
          ))}

          {/* Typing Indicator */}
          {isTyping && (
            <div className="flex gap-3 justify-start">
              <div className="w-8 h-8 bg-gradient-to-r from-purple-400 to-blue-400 rounded-full flex items-center justify-center">
                <Bot className="w-5 h-5 text-white" />
              </div>
              <Card className="bg-white/95 backdrop-blur-sm">
                <CardContent className="p-4">
                  <div className="flex gap-1">
                    <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                    <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{animationDelay: '0.1s'}}></div>
                    <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{animationDelay: '0.2s'}}></div>
                  </div>
                </CardContent>
              </Card>
            </div>
          )}
          
          <div ref={messagesEndRef} />
        </div>

        {/* Input Area */}
        <Card className="bg-white/95 backdrop-blur-sm">
          <CardContent className="p-4">
            <div className="flex gap-3">
              <Input
                ref={inputRef}
                value={inputValue}
                onChange={(e) => setInputValue(e.target.value)}
                onKeyPress={handleKeyPress}
                placeholder="Type your message..."
                className="flex-1 border-0 bg-transparent focus:ring-0 text-base"
                disabled={isTyping || !sessionId}
              />
              <Button
                onClick={handleSendMessage}
                disabled={!inputValue.trim() || isTyping || !sessionId}
                className="bg-gradient-to-r from-purple-500 to-blue-500 hover:from-purple-600 hover:to-blue-600"
              >
                {isTyping ? (
                  <Loader2 className="w-4 h-4 animate-spin" />
                ) : (
                  <Send className="w-4 h-4" />
                )}
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}

export default App

