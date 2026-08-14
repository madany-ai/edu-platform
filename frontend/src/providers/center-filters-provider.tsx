"use client";

import React, { createContext, useContext, useState, useEffect } from "react";
import { centerService } from "@/services/center.service";
import { useAuth } from "@/providers/auth-provider";

export interface AcademicYear {
  id: string;
  name: string;
  start_date?: string;
  end_date?: string;
  is_active: boolean;
}

interface CenterFiltersContextType {
  academicYears: AcademicYear[];
  selectedYearId: string;
  selectedGrade: string;
  selectedTerm: string;
  setSelectedYearId: (id: string) => void;
  setSelectedGrade: (grade: string) => void;
  setSelectedTerm: (term: string) => void;
  refreshAcademicYears: () => Promise<void>;
}

const CenterFiltersContext = createContext<CenterFiltersContextType | undefined>(undefined);

export function CenterFiltersProvider({ children }: { children: React.ReactNode }) {
  const [academicYears, setAcademicYears] = useState<AcademicYear[]>([]);
  const [selectedYearId, setSelectedYearId] = useState<string>("");
  const [selectedGrade, setSelectedGrade] = useState<string>("sec_1");
  const [selectedTerm, setSelectedTerm] = useState<string>("term_1");
  const [isInitialized, setIsInitialized] = useState(false);

  // Load from local storage on mount
  useEffect(() => {
    const storedYear = localStorage.getItem("lms_center_year");
    const storedGrade = localStorage.getItem("lms_center_grade");
    const storedTerm = localStorage.getItem("lms_center_term");

    if (storedYear) setSelectedYearId(storedYear);
    if (storedGrade) setSelectedGrade(storedGrade);
    if (storedTerm) setSelectedTerm(storedTerm);
    
    setIsInitialized(true);
  }, []);

  // Save to local storage when changed
  useEffect(() => {
    if (isInitialized) {
      localStorage.setItem("lms_center_year", selectedYearId);
      localStorage.setItem("lms_center_grade", selectedGrade);
      localStorage.setItem("lms_center_term", selectedTerm);
    }
  }, [selectedYearId, selectedGrade, selectedTerm, isInitialized]);

  const refreshAcademicYears = async () => {
    try {
      const res = await centerService.getAcademicYears();
      setAcademicYears(res || []);
      if (res?.length > 0 && !selectedYearId) {
        setSelectedYearId(res[0].id);
      }
    } catch (e) {
      console.error("Failed to load academic years:", e);
    }
  };

  const { user, isInstructor, isAssistant } = useAuth();
  const isStaff = isInstructor || isAssistant || user?.roles?.some((r: any) => ["instructor", "assistant", "super_admin", "admin"].includes(typeof r === "string" ? r : r.name));

  useEffect(() => {
    if (isStaff) {
      refreshAcademicYears();
    }
  }, [isStaff]);

  return (
    <CenterFiltersContext.Provider
      value={{
        academicYears,
        selectedYearId,
        selectedGrade,
        selectedTerm,
        setSelectedYearId,
        setSelectedGrade,
        setSelectedTerm,
        refreshAcademicYears,
      }}
    >
      {children}
    </CenterFiltersContext.Provider>
  );
}

export function useCenterFilters() {
  const context = useContext(CenterFiltersContext);
  if (context === undefined) {
    throw new Error("useCenterFilters must be used within a CenterFiltersProvider");
  }
  return context;
}
