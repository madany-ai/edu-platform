"use client";

import { useState } from "react";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { CreditCard, Wallet, Loader2 } from "lucide-react";
import { useCreateOrder } from "@/hooks/useProducts";
import { toast } from "sonner";

interface PaymentModalProps {
  isOpen: boolean;
  onClose: () => void;
  purchasableId: string;
  purchasableType: 'product' | 'bundle';
  price: number;
}

export function PaymentModal({ isOpen, onClose, purchasableId, purchasableType, price }: PaymentModalProps) {
  const [gateway, setGateway] = useState<'paymob' | 'fawry'>('paymob');
  const createOrder = useCreateOrder();

  const handleConfirm = () => {
    createOrder.mutate(
      {
        purchasable_id: purchasableId,
        purchasable_type: purchasableType,
        payment_gateway: gateway
      },
      {
        onSuccess: (res) => {
          if (res.payment_url) {
            window.location.href = res.payment_url;
          } else {
            toast.error("حدث خطأ، لم يتم استلام رابط الدفع من البوابة.");
          }
        },
        onError: (err: any) => {
          const message = err.response?.data?.message || "حدث خطأ غير متوقع أثناء إعداد عملية الدفع.";
          toast.error(message);
        }
      }
    );
  };

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="sm:max-w-md" dir="rtl">
        <DialogHeader>
          <DialogTitle className="text-xl">تأكيد عملية الدفع</DialogTitle>
          <DialogDescription>
            المبلغ المطلوب للدفع هو <span className="font-bold text-primary">{price} ج.م</span>
          </DialogDescription>
        </DialogHeader>

        <div className="flex flex-col gap-4 py-4">
          <p className="text-sm font-medium">اختر طريقة الدفع المناسبة لك:</p>
          
          <div className="grid grid-cols-2 gap-4">
            {/* Paymob Card */}
            <div 
              className={`border-2 rounded-xl p-4 cursor-pointer flex flex-col items-center justify-center gap-2 transition-all ${
                gateway === 'paymob' ? 'border-primary bg-primary/5' : 'border-border hover:border-primary/50'
              }`}
              onClick={() => setGateway('paymob')}
            >
              <CreditCard className={`h-8 w-8 ${gateway === 'paymob' ? 'text-primary' : 'text-muted-foreground'}`} />
              <span className={`font-semibold ${gateway === 'paymob' ? 'text-primary' : ''}`}>البطاقة البنكية</span>
            </div>

            {/* Fawry Card */}
            <div 
              className={`border-2 rounded-xl p-4 cursor-pointer flex flex-col items-center justify-center gap-2 transition-all ${
                gateway === 'fawry' ? 'border-yellow-500 bg-yellow-500/5' : 'border-border hover:border-yellow-500/50'
              }`}
              onClick={() => setGateway('fawry')}
            >
              <Wallet className={`h-8 w-8 ${gateway === 'fawry' ? 'text-yellow-500' : 'text-muted-foreground'}`} />
              <span className={`font-semibold ${gateway === 'fawry' ? 'text-yellow-500' : ''}`}>فوري (Fawry)</span>
            </div>
          </div>
        </div>

        <div className="flex justify-end gap-3 mt-4">
          <Button variant="outline" onClick={onClose} disabled={createOrder.isPending}>
            إلغاء
          </Button>
          <Button onClick={handleConfirm} disabled={createOrder.isPending}>
            {createOrder.isPending && <Loader2 className="ml-2 h-4 w-4 animate-spin" />}
            تأكيد الدفع
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}
