'use client'

import { useState, useEffect } from 'react'
import { subscribeUser, unsubscribeUser, sendNotification } from '@/app/actions'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Bell, BellOff, Send, Download } from 'lucide-react'

function urlBase64ToUint8Array(base64String: string) {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4)
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/')

  const rawData = window.atob(base64)
  const outputArray = new Uint8Array(rawData.length)

  for (let i = 0; i < rawData.length; ++i) {
    outputArray[i] = rawData.charCodeAt(i)
  }
  return outputArray
}

export function PwaManager() {
  const [isSupported, setIsSupported] = useState(false)
  const [subscription, setSubscription] = useState<PushSubscription | null>(null)
  const [message, setMessage] = useState('')
  const [isIOS, setIsIOS] = useState(false)
  const [isStandalone, setIsStandalone] = useState(false)

  useEffect(() => {
    if ('serviceWorker' in navigator && 'PushManager' in window) {
      setIsSupported(true)
      registerServiceWorker()
    }
    
    // Check for iOS and standalone mode
    setIsIOS(/iPad|iPhone|iPod/.test(navigator.userAgent) && !(window as any).MSStream)
    setIsStandalone(window.matchMedia('(display-mode: standalone)').matches)
  }, [])

  async function registerServiceWorker() {
    // We assume the service worker is already registered by pwa-register.tsx
    const registration = await navigator.serviceWorker.ready
    const sub = await registration.pushManager.getSubscription()
    setSubscription(sub)
  }

  async function subscribeToPush() {
    try {
      const registration = await navigator.serviceWorker.ready
      const sub = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(
          process.env.NEXT_PUBLIC_VAPID_PUBLIC_KEY!
        ),
      })
      setSubscription(sub)
      // The Next.js snippet serialization:
      const serializedSub = JSON.parse(JSON.stringify(sub))
      await subscribeUser(serializedSub)
    } catch (e) {
      console.error('Error subscribing to push:', e)
    }
  }

  async function unsubscribeFromPush() {
    await subscription?.unsubscribe()
    setSubscription(null)
    await unsubscribeUser()
  }

  async function sendTestNotification() {
    if (subscription) {
      await sendNotification(message || 'رسالة تجريبية من تطبيق حفني محمد')
      setMessage('')
    }
  }

  if (!isSupported && isStandalone) {
    return null
  }

  return (
    <div className="space-y-6 mt-4 p-4 border rounded-xl bg-surface/30">
      <h3 className="font-bold text-sm">إعدادات التطبيق والإشعارات</h3>
      
      {/* Install Prompt */}
      {!isStandalone && (
        <div className="space-y-2">
          <p className="text-xs text-muted-foreground">تثبيت التطبيق على جهازك</p>
          {isIOS ? (
            <p className="text-xs text-primary bg-primary/10 p-2 rounded">
              لتثبيت التطبيق على جهاز iOS الخاص بك، اضغط على زر المشاركة
              <span role="img" aria-label="share icon"> ⎋ </span>
              ثم اختر "Add to Home Screen"
              <span role="img" aria-label="plus icon"> ➕ </span>
            </p>
          ) : (
            <Button size="sm" variant="outline" className="w-full justify-start text-xs">
              <Download className="mr-2 h-4 w-4" />
              تثبيت التطبيق (Add to Home Screen)
            </Button>
          )}
        </div>
      )}

      {/* Push Notifications */}
      {isSupported && (
        <div className="space-y-4">
          <div className="flex flex-col gap-2">
            {subscription ? (
              <>
                <Button size="sm" variant="destructive" onClick={unsubscribeFromPush} className="w-full justify-start text-xs">
                  <BellOff className="mr-2 h-4 w-4" />
                  إلغاء الاشتراك من الإشعارات
                </Button>
                
                <div className="flex items-center gap-2 mt-2">
                  <Input 
                    type="text" 
                    placeholder="اكتب رسالة تجريبية..." 
                    value={message}
                    onChange={(e) => setMessage(e.target.value)}
                    className="h-8 text-xs"
                  />
                  <Button size="sm" onClick={sendTestNotification} className="px-3">
                    <Send className="h-4 w-4" />
                  </Button>
                </div>
              </>
            ) : (
              <Button size="sm" variant="outline" onClick={subscribeToPush} className="w-full justify-start text-xs border-primary text-primary hover:bg-primary hover:text-white">
                <Bell className="mr-2 h-4 w-4" />
                تفعيل الإشعارات
              </Button>
            )}
          </div>
        </div>
      )}
    </div>
  )
}
